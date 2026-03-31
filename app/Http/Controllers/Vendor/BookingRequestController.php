<?php

namespace App\Http\Controllers\Vendor;

use App\Constants\CarBookingConst;
use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\PushNotificationHelper;
use App\Models\Admin\BasicSettings;
use App\Models\Admin\TransactionSetting;
use App\Models\CarBooking;
use App\Models\UserNotification;
use App\Notifications\User\rideComplete;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Stripe\Balance;
use Illuminate\Support\Str;

class BookingRequestController extends Controller
{
    public function index(Request $request)
    {
        $page_title = __('Booking Request');
        $display_timezone = BasicSettings::first()?->timezone ?? config('app.timezone', 'UTC');
        $query = CarBooking::with(['cars.type', 'cars.carModel', 'user'])
            ->where(function ($q) {
                $q->whereHas('cars', function ($subquery) {
                    $subquery->where('vendor_id', '=', auth()->guard('vendor')->user()->id);
                });
            });

        // Search Filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('trx_id', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('firstname', 'like', "%$search%")
                            ->orWhere('lastname', 'like', "%$search%");
                    });
            });
        }

        // Status Filter
        if ($request->filled('status')) {
            if ($request->status == '0') {
                // Show both status 0 and 1 for Pending
                $query->whereIn('status', [0, 1]);
            } elseif (in_array($request->status, [2, 3, 4])) {
                $query->where('status', $request->status);
            }
        }

        $car_bookings = $query->orderByDesc('id')->paginate(7);

        return view('vendor-end.sections.booking.booking-request', compact('car_bookings', 'page_title', 'display_timezone'));
    }

    public function details($id)
    {
        $page_title = __('Booking Details');
        $display_timezone = BasicSettings::first()?->timezone ?? config('app.timezone', 'UTC');
        $booking = CarBooking::with(['cars', 'user', 'bookingTransactions'])->where('id', $id)->whereHas('cars', function ($subquery) {
            $subquery->where('vendor_id', '=', auth()->guard('vendor')->user()->id);
        })->firstOrFail();

        return view('vendor-end.sections.booking.details', compact('booking', 'page_title', 'display_timezone'));
    }

    public function accept($id)
    {
        $charges = TransactionSetting::where('slug', 'cash')->first();
        $max_limit = $charges->max_limit;

        $info = CarBooking::where('id', $id)->first();
        if ($max_limit <= $info->cars->vendor->wallets->due_payment) {
            return back()->with(['warning' => [__('Please pay your due amount')]]);
        }

        $basic_setting = BasicSettings::first();
        try {
            $info->update(['status' => 2]);
            $notification_content = [
                'title'   => "Request Accepted",
                'message' => "Vendor accepted your request",
                'time'    => Carbon::now()->diffForHumans(),
                'image'   => files_asset_path('profile-default'),
            ];
            UserNotification::create([
                'type'    => NotificationConst::REQUEST_ACCEPT,
                'user_id' => $info->user_id,
                'message' => $notification_content,
            ]);

            try {
                if ($basic_setting->push_notification) {
                    (new PushNotificationHelper())
                        ->prepare(
                            [$info->user_id],
                            [
                                'title' => $notification_content['title'],
                                'desc' => $notification_content['message'],
                                'user_type' => 'user',
                            ],
                        )
                        ->send();
                }
            } catch (Exception $e) {
            }
        } catch (Exception $e) {
            return back()->with(['error' => [__('Oops! Something went wrong! Please try again')]]);
        }
        return back()->with(['success' => [__('Request Accepted Successfully')]]);
    }

    public function reject(Request $request, $id)
    {
        // Validate rejection reason
        $request->validate([
            'reason' => 'required|string',
            'custom_reason' => 'nullable|required_if:reason,other|string|max:1000',
        ]);

        // Determine the final rejection reason
        $rejectionReason = $request->reason === 'other'
            ? $request->custom_reason
            : $request->reason;

        $booking_info = CarBooking::where('id', $id)->first();
        $basic_setting = BasicSettings::first();
        try {

            if ($booking_info->payment_type == Str::slug(CarBookingConst::ONLINE_PAYMENT) && $booking_info->transaction->status == PaymentGatewayConst::STATUSSUCCESS) {
                $booking_info->transaction->update([
                    'refundable' => PaymentGatewayConst::STATUSPENDING,
                ]);
            };

            // Update booking status and store rejection reason
            $booking_info->update([
                'status' => 4,
                'rejection_reason' => $rejectionReason,
            ]);

            $notification_content = [
                'title'   => "Booking Rejected",
                'message' => "Your booking #{$booking_info->trx_id} was rejected. Reason: {$rejectionReason}",
                'time'    => Carbon::now()->diffForHumans(),
                'image'   => files_asset_path('profile-default'),
            ];
            UserNotification::create([
                'type'    => NotificationConst::REQUEST_REJECTED,
                'user_id' => $booking_info->user_id,
                'message' => $notification_content,
            ]);

            try {
                if ($basic_setting->push_notification) {
                    Log::info('Attempting to send push notification', [
                        'user_id' => $booking_info->user_id,
                        'title' => $notification_content['title'],
                        'message' => $notification_content['message'],
                    ]);

                    $result = (new PushNotificationHelper())
                        ->prepare(
                            [$booking_info->user_id],
                            [
                                'title' => $notification_content['title'],
                                'desc' => $notification_content['message'],
                                'user_type' => 'user',
                            ],
                        )
                        ->send();

                    Log::info('Push notification sent successfully', ['result' => $result]);
                } else {
                    Log::info('Push notification is disabled in settings');
                }
            } catch (Exception $e) {
                // Log the error for debugging
                Log::error('Push notification failed: ' . $e->getMessage(), [
                    'exception' => $e,
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        } catch (Exception $e) {
            return back()->with(['danger' => [__('Oops! Something went wrong! Please try again')]]);
        }

        return back()->with(['danger' => [__('Request Rejected')]]);
    }

    public function complete($id)
    {
        $info = CarBooking::where('id', $id)->first();
        $basic_setting = BasicSettings::first();

        if (!$info) {
            return back()->with(['danger' => [__('Oops! Something went wrong! Please try again')]]);
        }
        try {
            if ($info->payment_type == Str::slug(CarBookingConst::ONLINE_PAYMENT) && $info->transaction->status == PaymentGatewayConst::STATUSSUCCESS) {
                $wallet = auth()->guard('vendor')->user()->wallets;
                if (!$wallet) {
                    return back()->with(['danger' => [__("Vendor wallet couldn't found")]]);
                }

                if ($wallet->due_payment != 0 && $wallet->due_payment == $info->transaction->receive_amount) {
                    $charge = $wallet->due_payment;
                    $this->insertProfit($charge);
                    $wallet->update([
                        'due_payment' => 0,
                    ]);
                } elseif ($wallet->due_payment != 0 && $wallet->due_payment < $info->transaction->receive_amount) {
                    $charge = $wallet->due_payment;
                    $this->insertProfit($charge);
                    $wallet->update([
                        'balance' => $wallet->balance + ($info->transaction->receive_amount - $wallet->due_payment),
                        'due_payment' => 0,
                    ]);
                } elseif ($wallet->due_payment != 0 && $wallet->due_payment > $info->transaction->receive_amount) {
                    $charge = $info->transaction->receive_amount;
                    $this->insertProfit($charge);
                    $wallet->update([
                        'due_payment' => $wallet->due_payment - $info->transaction->receive_amount,
                    ]);
                } elseif ($wallet->due_payment == 0) {
                    $wallet->update([
                        'balance' => $wallet->balance + $info->transaction->receive_amount,
                    ]);
                }
            }

            if ($info->payment_type == Str::slug(CarBookingConst::CASH)) {
                $wallet = auth()->guard('vendor')->user()->wallets;
                if (!$wallet) {
                    return back()->with(['danger' => [__("Vendor wallet couldn't found")]]);
                }
                $wallet->update([
                    'due_payment' => $wallet->due_payment + $info->charges,
                ]);
            }

            $info->update([
                'status' => CarBookingConst::STATUSCOMPLETE,
            ]);
            $notification_content = [
                'title'   => "Ride Complete",
                'message' => "You have completed your ride",
                'time'    => Carbon::now()->diffForHumans(),
                'image'   => files_asset_path('profile-default'),
            ];
            UserNotification::create([
                'type'    => NotificationConst::RIDE_COMPLETE,
                'user_id' => $info->user_id,
                'message' => $notification_content,
            ]);
            try {
                if ($basic_setting->push_notification) {
                    (new PushNotificationHelper())
                        ->prepare(
                            [$info->user_id],
                            [
                                'title' => $notification_content['title'],
                                'desc' => $notification_content['message'],
                                'user_type' => 'user',
                            ],
                        )
                        ->send();
                }

                if ($basic_setting->email_notification) {

                    Notification::route('mail', $info->email)->notify(new rideComplete($info));
                }
            } catch (Exception $e) {
            }
        } catch (Exception $e) {
            return back()->with(['danger' => [__('Oops! Something went wrong! Please try again')]]);
        }
        return back()->with(['success' => [__('Tour Complete')]]);
    }


    public function insertProfit($charge)
    {
        DB::beginTransaction();
        try {
            DB::table('admin_profits')->insert([
                'percent_charge'    => 0,
                'fixed_charge'      => 0,
                'total_charge'      => $charge,
                'created_at'        => now(),
            ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }
}
