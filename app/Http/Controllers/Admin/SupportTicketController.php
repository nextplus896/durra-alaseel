<?php

namespace App\Http\Controllers\Admin;

use App\Constants\GlobalConst;
use App\Constants\SupportTicketConst;
use App\Http\Controllers\Controller;
use App\Models\UserSupportChat;
use App\Models\UserSupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Helpers\Response;
use Exception;
use App\Events\Admin\SupportConversationEvent;
use App\Models\Admin\BasicSettings;
use App\Models\User;
use App\Models\UserSupportTicketAttachment;
use App\Models\Vendor\Vendor;
use App\Notifications\Admin\NewUserNotification;
use App\Notifications\Admin\SupportTicketNotification;
use App\Traits\User\RegisteredUsers;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class SupportTicketController extends Controller
{
    use RegisteredUsers;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_title = __("All Ticket");
        $support_tickets = UserSupportTicket::orderByDesc("id")->get();
        $all_tickets = UserSupportTicket::orderByDesc("id")->get();
        return view('admin.sections.support-ticket.index', compact(
            'page_title',
            'support_tickets',
            'all_tickets'
        ));
    }


    /**
     * Display The Pending List of Support Ticket
     *
     * @return view
     */
    public function pending() {
        $page_title = __("Pending Ticket");
        $support_tickets = UserSupportTicket::pending()->orderByDesc("id")->get();
        $all_tickets = UserSupportTicket::orderByDesc("id")->get();
        return view('admin.sections.support-ticket.index', compact(
            'page_title',
            'support_tickets',
            'all_tickets'
        ));
    }


    /**
     * Display The Active List of Support Ticket
     *
     * @return view
     */
    public function active() {
        $page_title = __("Active Ticket");
        $support_tickets = UserSupportTicket::active()->orderByDesc("id")->get();
        $all_tickets = UserSupportTicket::orderByDesc("id")->get();
        return view('admin.sections.support-ticket.index', compact(
            'page_title',
            'support_tickets',
            'all_tickets'
        ));
    }


    /**
     * Display The Solved List of Support Ticket
     *
     * @return view
     */
    public function solved() {
        $page_title = __("Solved Ticket");
        $support_tickets = UserSupportTicket::solved()->orderByDesc("id")->get();
        $all_tickets = UserSupportTicket::orderByDesc("id")->get();
        return view('admin.sections.support-ticket.index', compact(
            'page_title',
            'support_tickets',
            'all_tickets'
        ));
    }


    public function conversation($encrypt_id) {
        $support_ticket_id = decrypt($encrypt_id);
        $support_ticket = UserSupportTicket::findOrFail($support_ticket_id);
        $page_title = __("Support Chat");
        return view('admin.sections.support-ticket.conversation',compact(
            'page_title',
            'support_ticket',
        ));
    }


    public function messageReply(Request $request) {
        $validator = Validator::make($request->all(),[
            'message'       => 'required|string|max:200',
            'support_token' => 'required|string|exists:user_support_tickets,token',
        ]);
        if($validator->fails()) {
            $error = ['error' => $validator->errors()];
            return Response::error($error,null,400);
        }
        $validated = $validator->validate();

        $support_ticket = UserSupportTicket::notSolved($validated['support_token'])->first();
        if(!$support_ticket) return Response::error(['error' => [__('This support ticket is closed.')]]);

        $data = [
            'user_support_ticket_id'    => $support_ticket->id,
            'sender'                    => auth()->user()->id,
            'sender_type'               => "ADMIN",
            'message'                   => $validated['message'],
            'receiver_type'             => "USER",
            'receiver'                  => $support_ticket->user_id,
        ];

        try{
            $chat_data = UserSupportChat::create($data);
        }catch(Exception $e) {
            $error = ['error' => [__('SMS Sending failed! Please try again.')]];
            return Response::error($error,null,500);
        }

        try{
            event(new SupportConversationEvent($support_ticket,$chat_data));
        }catch(Exception $e) {
            $error = ['error' => [__('SMS Sending failed! Please try again.')]];
            return Response::error($error,null,500);
        }

        if($support_ticket->status != SupportTicketConst::ACTIVE) {
            try{
                $support_ticket->update([
                    'status'    => SupportTicketConst::ACTIVE,
                ]);
            }catch(Exception $e) {
                $error = ['error' => [__('Failed to change status to active!')]];
                return Response::error($error,null,500);
            }
        }
    }

    public function solve(Request $request) {
        $validator = Validator::make($request->all(),[
            'target'    => 'required|string|exists:user_support_tickets,token',
        ]);
        $validated = $validator->validate();

        $support_ticket = UserSupportTicket::where("token",$validated['target'])->first();
        if($support_ticket->status == SupportTicketConst::SOLVED) return back()->with(['warning' => [__('This ticket is already solved!')]]);

        try{
            $support_ticket->update([
                'status'        => SupportTicketConst::SOLVED,
            ]);
        }catch(Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }

        return back()->with(['success' => [__('Success')]]);
    }

    /**
     * Method for delete multiple tickets
     * @param Illuminate\Http\Request $request
     */
    public function bulkDelete(Request $request){
        $validator = Validator::make($request->all(), [
            'target' => 'required',
        ]);
        if ($validator->fails()) {
            return Response::error($validator->errors()->all());
        }

        $validated = $validator->validate();

        $ticket_ids       = array_map('intval', explode(',', $validated['target']));

        try{
            UserSupportTicket::whereIn('id', $ticket_ids)->delete();
        }catch(Exception $e){
            return back()->with(['error' => [__("Something went wrong! Please try again")]]);
        }

        return back()->with(['success' => ['Support tickets deleted successfully.']]);
    }
    /**
     * Method for delete support ticket information
     * @param Illuminate\Http\Request $request
     */
    public function delete(Request $request) {
        $validator = Validator::make($request->all(),[
            'target'        => 'required',
        ]);
        $validated = $validator->validate();

        $support_ticket = UserSupportTicket::where("id",$validated['target'])->first();

        try{
            $support_ticket->delete();
        }catch(Exception $e) {
            return back()->with(['error' => [__("Something went wrong! Please try again")]]);
        }

        return back()->with(['success' => ['Support ticket deleted successfully.']]);
    }


     /**
     * Method for view create support ticket page
     * @return view
     */
    public function create(){
        $page_title         = __("Create Support Ticket");

        return view('admin.sections.support-ticket.create',compact(
            'page_title'
        ));
    }

    /**
     * Method for check user
     * @param Illuminate\Http\Request $request
     */
    public function checkUser(Request $request){
        $validator      = Validator::make($request->all(),[
            'user_type' => 'required|string',
            'email'     => 'required|email'
        ]);
        $validated      = $validator->validate();

        // Determine model by user type
        switch ($validated['user_type']) {
            case GlobalConst::USER:
                $model = User::class;
                break;
            case GlobalConst::VENDOR:
                $model = Vendor::class;
                break;
            default:
                return response()->json(['not_exists' =>[__('Unregistered user.')]]);
        }

        $user['data']   = $model::where('email', $validated['email'])->first();
        if(!$user['data']) return response()->json(['not_exists' =>[__('Unregistered user.')]]);
        return response($user);

    }
    /**
     * Method for store support ticket information
     * @param Illuminate\Http\Request $request
     */
    public function store(Request $request){
        $validator          = Validator::make($request->all(),[
            'email'         => 'required|email',
            'is_registered' => 'required',
            'user_type'     => 'required',
            'firstname'     => 'required_if:is_registered,==' . SupportTicketConst::NEWUSER,
            'lastname'      => 'required_if:is_registered,==' . SupportTicketConst::NEWUSER,
            'password'      => 'required_if:is_registered,==' . SupportTicketConst::NEWUSER,
            'subject'       => 'required|string',
            'message'       => 'required',
            'attachment.*'  => "nullable|max:204800",
        ]);
        if($validator->fails()){
            return back()->withErrors($validator)->withInput($request->all());
        }

        $validated          = $validator->validate();

        $basic_settings     = BasicSettings::first();

        // Determine model by user type
        switch ($validated['user_type']) {
            case GlobalConst::USER:
                $model               = User::class;
                $user_field          = "user_id";
                $email_notify_status = $basic_settings->email_notification;
                $user_type           = "USER";
                $table_name          = "users";
                break;
            case GlobalConst::VENDOR:
                $model               = Vendor::class;
                $user_field          = "vendor_id";
                $email_notify_status = $basic_settings->agent_email_notification;
                $user_type           = "VENDOR";
                $table_name          = "vendors";
                break;
            default:
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }



        if($validated['is_registered'] == SupportTicketConst::USER_STATUS){
            $user                       = $model::where('email',$validated['email'])->first();
            $validated['token']         = generate_unique_string('user_support_tickets','token');
            $validated[$user_field]     = $user->id;
            $validated['admin_id']      = auth()->user()->id;
            $validated['status']        = 0;
            $validated['created_at']    = now();
            $validated = Arr::except($validated,['is_registered','user_type','firstname','lastname','password','attachment']);

            try{
                $support_ticket_id = UserSupportTicket::insertGetId($validated);
                if($email_notify_status == true){
                    try{
                        Notification::route('mail',$user->email)->notify(new SupportTicketNotification($user,$support_ticket_id));
                    }catch(Exception $e){}
                }
            }
            catch(Exception $e){
                return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
            }

            if($request->hasFile('attachment')) {
                $validated_files = $request->file("attachment");
                $attachment = [];
                $files_link = [];
                foreach($validated_files as $item) {
                    $upload_file = upload_file($item,'support-attachment');
                    if($upload_file != false) {
                        $attachment[] = [
                            'user_support_ticket_id'    => $support_ticket_id,
                            'attachment'                => $upload_file['name'],
                            'attachment_info'           => json_encode($upload_file),
                            'created_at'                => now(),
                        ];
                    }

                    $files_link[] = get_files_path('support-attachment') . "/". $upload_file['name'];
                }

                try{
                    UserSupportTicketAttachment::insert($attachment);
                }catch(Exception $e) {
                    $support_ticket_id->delete();
                    delete_files($files_link);

                    return back()->with(['error' => [__("Ops! Failed To Upload Image.")]]);
                }
            }

            return redirect()->route('admin.support.ticket.index')->with(['success' => [__("Support ticket created successfully.")]]);
        }else{



            $user_name              = make_username(Str::slug($validated['firstname']),Str::slug($validated['lastname']),$table_name);

            $user_data['firstname']      = $validated['firstname'];
            $user_data['lastname']       = $validated['lastname'];
            $user_data['email']          = $validated['email'];
            $user_data['username']       = $user_name;
            $user_data['password']       = Hash::make($validated['password']);
            $user_data['status']         = true;
            $user_data['email_verified'] = true;
            $user_data['sms_verified']   = true;
            $user_data['kyc_verified']   = true;
            try{
                $user =  $model::create($user_data);
                if ($user_type == GlobalConst::VENDOR) {
                    $this->createUserWallets($user);
                }
                if($email_notify_status){
                    try{
                        Notification::route('mail',$validated['email'])->notify(new NewUserNotification($data = $user_data,$request->password,$user_type));
                    }catch(Exception $e){}
                }
            }catch(Exception $e){

                return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
            }


            $validated['token']         = generate_unique_string('user_support_tickets','token');
            $validated[$user_field]     = $user->id;
            $validated['admin_id']      = auth()->user()->id;
            $validated['status']        = 0;
            $validated['created_at']    = now();
            $validated = Arr::except($validated,['is_registered','user_type','firstname','lastname','password','attachment']);

            try{
                $support_ticket_id = UserSupportTicket::insertGetId($validated);

                if($email_notify_status == true){
                    try{
                        Notification::route('mail',$user->email)->notify(new SupportTicketNotification($user,$support_ticket_id));
                    }catch(Exception $e){}
                }
            }
            catch(Exception $e) {
                return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
            }

            if($request->hasFile('attachment')) {
                $validated_files = $request->file("attachment");
                $attachment = [];
                $files_link = [];
                foreach($validated_files as $item) {
                    $upload_file = upload_file($item,'support-attachment');
                    if($upload_file != false) {
                        $attachment[] = [
                            'user_support_ticket_id'    => $support_ticket_id,
                            'attachment'                => $upload_file['name'],
                            'attachment_info'           => json_encode($upload_file),
                            'created_at'                => now(),
                        ];
                    }

                    $files_link[] = get_files_path('support-attachment') . "/". $upload_file['name'];
                }

                try{
                    UserSupportTicketAttachment::insert($attachment);
                }catch(Exception $e) {
                    $support_ticket_id->delete();
                    delete_files($files_link);

                    return back()->with(['error' => [__("Ops! Failed To Upload Image.")]]);
                }
            }
            return redirect()->route('admin.support.ticket.index')->with(['success' => [__("Support ticket created successfully.")]]);
        }
    }
}
