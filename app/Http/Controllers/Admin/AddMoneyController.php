<?php

namespace App\Http\Controllers\Admin;

use App\Constants\CarBookingConst;
use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Response;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\User\AddMoney\ApprovedByAdminMail;
use Carbon\Carbon;
use Exception;
use App\Events\User\NotificationEvent as UserNotificationEvent;
use App\Models\Admin\BasicSettings;
use App\Models\Vendor\VendorNotification;
use App\Notifications\User\AddMoney\RejectedByAdminMail;
use App\Models\Vendor\VendorWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AddMoneyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $page_title = __('All Logs');
        $page_slug  = Str::slug(CarBookingConst::CAR_INDEX);
        $transactions = Transaction::with('user:id,firstname,email,username,mobile', 'payment_gateway:id,name')->where('type', 'add-money')->paginate(20);
        return view('admin.sections.add-money.index', compact('page_title', 'page_slug', 'transactions'));
    }

    /**
     * Pending Add Money Logs View.
     */
    public function pending()
    {
        $page_title = __('Pending Logs');
        $page_slug  = Str::slug(CarBookingConst::CAR_PENDING);
        $transactions = Transaction::with('user:id,firstname,email,username,mobile', 'payment_gateway:id,name')->where('type', 'add-money')->where('status', PaymentGatewayConst::STATUSPENDING)->paginate(20);
        return view('admin.sections.add-money.index', compact('page_title', 'page_slug', 'transactions'));
    }

    /**
     * Complete Add Money Logs View.
     */
    public function complete()
    {
        $page_title = __('Complete Logs');
        $page_slug  = Str::slug(CarBookingConst::CAR_COMPLETE);
        $transactions = Transaction::with('user:id,firstname,email,username,mobile', 'payment_gateway:id,name')->where('type', 'add-money')->where('status', PaymentGatewayConst::STATUSSUCCESS)->paginate(20);
        return view('admin.sections.add-money.index', compact('page_title', 'page_slug', 'transactions'));
    }

    /**
     * Canceled Add Money Logs View.
     */
    public function canceled()
    {
        $page_title = __('Canceled Logs');
        $page_slug  = Str::slug(CarBookingConst::CAR_CENCEL);
        $transactions = Transaction::with('user:id,firstname,email,username,mobile', 'payment_gateway:id,name')->where('type', 'add-money')->where('status', PaymentGatewayConst::STATUSREJECTED)->paginate(20);
        return view('admin.sections.add-money.index', compact('page_title', 'page_slug', 'transactions'));
    }

    /**
     * Refund Logs View.
     */
    public function refund()
    {
        $page_title = __('Refund Logs');
        $page_slug  = Str::slug(CarBookingConst::CAR_REFUND);
        $transactions = Transaction::with('user:id,firstname,email,username,mobile', 'payment_gateway:id,name')
            ->where('type', 'add-money')
            ->where(function ($query) {
                $query->whereHas('bookings', function ($subquery) {
                    $subquery->where('status', '=', 4);
                });
            })
            ->paginate(20);
        return view('admin.sections.add-money.index', compact('page_title', 'page_slug', 'transactions'));
    }

    public function addMoneyDetails($id)
    {
        $data = Transaction::where('id', $id)->with('user:id,firstname,lastname,email,username,full_mobile', 'gateway_currency:id,name,alias,payment_gateway_id,currency_code,rate', 'payment_gateway:type')->where('type', 'add-money')->first();
        $page_title = __('Payment details for') . '  ' . $data->trx_id;
        return view('admin.sections.add-money.details', compact('page_title', 'data'));
    }

    /**
     * Approve a pending add-money transaction.
     * Uses DB::transaction + lockForUpdate to prevent concurrent double-approval.
     */
    public function approved(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::transaction(function () use ($request) {
                $data = Transaction::lockForUpdate()
                    ->where('id', $request->id)
                    ->where('status', PaymentGatewayConst::STATUSPENDING)
                    ->where('type', 'add-money')
                    ->first();

                if (!$data) {
                    throw new Exception(__('Transaction not found or already processed.'));
                }

                $amount = $data->receive_amount;

                // Safely credit vendor wallet if booking is complete
                if (
                    $data->bookings
                    && $data->bookings->cars
                    && $data->bookings->cars->vendor
                    && $data->bookings->status == CarBookingConst::STATUSCOMPLETE
                ) {
                    $vendorWallet = VendorWallet::lockForUpdate()
                        ->where('vendor_id', $data->bookings->cars->vendor->id)
                        ->first();

                    if ($vendorWallet) {
                        $newBalance = bcadd((string) $vendorWallet->balance, (string) $amount, 8);
                        $vendorWallet->update(['balance' => $newBalance]);
                    }
                }

                $data->update(['status' => PaymentGatewayConst::STATUSSUCCESS]);

                $user = User::where('id', $data->user_id)->first();
                $basic_setting = BasicSettings::first();

                // User Notification
                $notification_content = [
                    'title'   => "Payment Approved",
                    'message' => "Admin approved your payment",
                    'time'    => Carbon::now()->diffForHumans(),
                    'image'   => files_asset_path('profile-default'),
                ];

                UserNotification::create([
                    'type'    => NotificationConst::PAYMENT,
                    'user_id' => $user->id,
                    'message' => $notification_content,
                ]);

                // Mail notification
                if ($basic_setting->email_notification == true) {
                    $user->notify(new ApprovedByAdminMail($user, $data));
                }

                // Vendor Notification
                if (isset($vendorWallet)) {
                    $vendor_notification = $notification_content;
                    $vendor_notification['message'] = 'Due payment added to your wallet';
                    VendorNotification::create([
                        'type'      => NotificationConst::PAYMENT,
                        'vendor_id' => $data->bookings->cars->vendor->id,
                        'message'   => $vendor_notification,
                    ]);
                }

                // Push Notifications
                event(new UserNotificationEvent($notification_content, $user));
                send_push_notification(["user-" . $user->id], [
                    'title' => $notification_content['title'],
                    'body'  => $notification_content['message'],
                    'icon'  => $notification_content['image'],
                ]);
            });
        } catch (Exception $e) {
            return back()->with(['error' => [$e->getMessage()]]);
        }

        return back()->with(['success' => ['Payment approved successfully']]);
    }

    /**
     * Reject a pending add-money transaction.
     */
    public function rejected(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'reject_reason' => 'required|string|max:200',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $data = Transaction::where('id', $request->id)
                ->where('status', PaymentGatewayConst::STATUSPENDING)
                ->where('type', 'add-money')
                ->first();

            if (!$data) {
                return back()->with(['error' => [__('Transaction not found or already processed.')]]);
            }

            $data->update([
                'status'        => PaymentGatewayConst::STATUSREJECTED,
                'reject_reason' => $request->reject_reason,
            ]);

            $user = User::where('id', $data->user_id)->first();

            // Mail notification
            $user->notify(new RejectedByAdminMail($user, $data));

            // Notification created
            $notification_content = [
                'title'   => "Payment Rejected",
                'message' => "Admin Rejected your payment",
                'time'    => Carbon::now()->diffForHumans(),
                'image'   => files_asset_path('profile-default'),
                'trx_id'  => $data->trx_id,
            ];
            UserNotification::create([
                'type'    => NotificationConst::PAYMENT_REJECT,
                'user_id' => $user->id,
                'message' => $notification_content,
            ]);

            // Push Notifications
            event(new UserNotificationEvent($notification_content, $user));
            send_push_notification(["user-" . $user->id], [
                'title' => $notification_content['title'],
                'body'  => $notification_content['message'],
                'icon'  => $notification_content['image'],
            ]);
        } catch (Exception $e) {
            return back()->with(['error' => [$e->getMessage()]]);
        }

        return back()->with(['success' => ['Payment request rejected successfully']]);
    }

    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'target'       => "required|exists:transactions,trx_id",
            'status'       => "required",
        ]);

        if ($validator->fails()) return back()->withErrors($validator)->withInput()->with('modal', 'status-change');
        $validated = $validator->validate();
        $info = Transaction::where('trx_id', $validated['target'])->first();

        try {
            $info->update([
                'refundable' => $validated['status'],
            ]);
            if ($validated['status'] == PaymentGatewayConst::STATUSSUCCESS) {
                $notification_content = [
                    'title'   => "Payment Refunded",
                    'message' => "Admin refunded your payment",
                    'time'    => Carbon::now()->diffForHumans(),
                    'image'   => files_asset_path('profile-default'),
                ];
                UserNotification::create([
                    'type'    => NotificationConst::PAYMENT_REFUNDED,
                    'user_id' => $info->user_id,
                    'message' => $notification_content,
                ]);
            }
        } catch (Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again')]]);
        }
        return back()->with(['success' => [__('Status updated successfully!')]]);
    }

    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string',
        ]);
        if ($validator->fails()) {
            $error = ['error' => $validator->errors()];
            return Response::error($error, null, 400);
        }
        $validated = $validator->validate();

        $transactions = Transaction::with('user:id,firstname,email,username,mobile', 'payment_gateway:id,name')
            ->where('type', 'add-money')
            ->where('trx_id', 'like', '%' . $validated['text'] . '%')
            ->latest()
            ->paginate(20);
        return view('admin.components.search.add-money.transaction_search', compact('transactions'));
    }
}
