<?php
namespace App\Traits\PaymentGateway;

use App\Constants\GlobalConst;
use Exception;
use App\Models\TemporaryData;
use App\Constants\PaymentGatewayConst;
use App\Models\Admin\BasicSettings;
use App\Models\Admin\PaymentGateway;
use App\Models\CarBooking;
use Carbon\Carbon;

use Illuminate\Support\Facades\DB;


trait Authorize{

    /**
     * Convert time to 24-hour format (HH:mm) if it's in 12-hour format (h:i A)
     */
    private function convertTo24HourFormat($time)
    {
        try {
            // If time contains AM/PM, convert it to 24-hour format
            if (preg_match('/(AM|PM|am|pm)/', $time)) {
                return Carbon::parse($time)->format('H:i');
            }
            // Already in 24-hour format, return as-is
            return $time;
        } catch (\Exception $e) {
            // If parsing fails, return original
            return $time;
        }
    }

    public function authorizeInit($output = null){
        if(!$output) $output = $this->output;
        if($output['type'] === PaymentGatewayConst::TYPEADDMONEY){
            return $this->setupAuthorizeInitAddMoney($output);
        }
    }

    public function setupAuthorizeInitAddMoney($output,){
        $junk_data = $this->authorizeJunkInsert();
        if(request()->expectsJson()) {
            $data = [
                'redirect_url' => route('api.user.car.booking.authorize.payment'),
                'redirect_links' => [],
                'type' => PaymentGatewayConst::TYPEADDMONEY,
                'address_info' => [],
                'identifier'   => $junk_data->identifier,
            ];

            return $data;
        }
        return redirect()->route('user.car.booking.authorize.card.info',$junk_data->identifier);
    }
    public function authorizeJunkInsert() {
        $output = $this->output;
        $temp_record_token = generate_unique_string('temporary_datas', 'identifier', 60);

        $data = [
            'gateway'       => $output['gateway']->id,
            'currency'      => $output['currency']->id,
            'amount'        => json_decode(json_encode($output['amount']),true),
            'creator_table' => auth()->guard(get_auth_guard())->user()->getTable(),
            'creator_id'    => auth()->guard(get_auth_guard())->user()->id,
            'creator_guard' => get_auth_guard(),
            'booking_token' => $output['form_data']['token'],
        ];

        return TemporaryData::create([
            'type'          => PaymentGatewayConst::AUTHORIZE,
            'identifier'    => $temp_record_token,
            'data'          => $data,
        ]);
    }
    public function authorizeInitApi($output = null){
        if(!$output) $output = $this->output;
        if($output['type'] === PaymentGatewayConst::TYPEADDMONEY){
            return $this->setupAuthorizeInitAddMoney($output);
        }
    }

    // For get the gateway credentials
    function authorizeCredentials($temp_data){
        $gateway             = PaymentGateway::where('id',$temp_data->data->gateway)->first() ?? null;
        if(!$gateway) throw new Exception(__("Payment gateway not available"));
        $credentials         = $gateway->credentials;
        $app_login_id        = getPaymentCredentials($credentials,'App Login ID');
        $transaction_key     = getPaymentCredentials($credentials,'Transaction Key');
        $signature_key       = getPaymentCredentials($credentials,'Signature Key');

        $mode           = $gateway->env;

        $authorize_register_mode = [
            PaymentGatewayConst::ENV_SANDBOX => "sandbox",
            PaymentGatewayConst::ENV_PRODUCTION => "live",
        ];
        if(array_key_exists($mode,$authorize_register_mode)) {
            $mode = $authorize_register_mode[$mode];
        }else {
            $mode = "sandbox";
        }

        return (object) [
            'app_login_id'          => $app_login_id,
            'transaction_key'       => $transaction_key,
            'signature_key'         => $signature_key,
            'mode'                  => $mode,
            'code'                  => $gateway->code
        ];
    }

    // Fro insert data in db
    function createTransactionAuthorize($trx_id,$output,$status){
        $trx_id = generate_unique_string("transactions","trx_id",16);
        $user_id = auth()->user()->id;

        $gateway = PaymentGateway::where('id',$output->data->gateway)->first();

        $this->insertRecordAuthorize($output,$trx_id,$user_id,$gateway);

    }

    public function insertRecordAuthorize($output,$trx_id){
        $status = PaymentGatewayConst::STATUSSUCCESS;
        DB::beginTransaction();
        // dd($output);
        try{
            $id = DB::table("transactions")->insertGetId([
                'type'                          => PaymentGatewayConst::TYPEADDMONEY,
                'trx_id'                        => $trx_id,
                'user_type'                     => GlobalConst::USER,
                'user_id'                       => $output->data->creator_id,
                'payment_gateway_currency_id'   => $output->data->currency,
                'request_amount'                => $output->data->amount->requested_amount,
                'request_currency'              => get_default_currency_code(),
                'exchange_rate'                 => $output->data->amount->exchange_rate,
                'percent_charge'                => $output->data->amount->percent_charge,
                'fixed_charge'                  => $output->data->amount->fixed_charge,
                'total_charge'                  => $output->data->amount->total_charge,
                'total_payable'                 => $output->data->amount->total_amount,
                'receive_amount'                => $output->data->amount->requested_amount,
                'available_balance'             => 0,
                'payment_currency'              => $output->data->amount->default_currency,
                'remark'                        => ucwords(remove_special_char(PaymentGatewayConst::TYPEADDMONEY," ")) . " With " . $output->type,
                'details'                       => json_encode(['gateway_response' => $output['capture']]),
                'status'                        => $status,
                'callback_ref'                  => null,
                'booking_token'                 => $output->data->booking_token,
                'created_at'                    => now(),
            ]);

            $booking_token = $output->data->booking_token;
            $type = 'online-payment';


            $this->authorizeBookingConfirm($booking_token, $type, $trx_id);

            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again."));
        }
    }


    public function authorizeBookingConfirm($token, $type, $trx_id)
    {
        $temp_booking = TemporaryData::where('identifier', $token)->first();
        $basic_setting = BasicSettings::first();
        $temp_data = json_decode(json_encode($temp_booking->data), true);

        // Convert time formats to 24-hour format if needed
        if (isset($temp_data['pickup_time'])) {
            $temp_data['pickup_time'] = $this->convertTo24HourFormat($temp_data['pickup_time']);
        }
        if (isset($temp_data['round_pickup_time']) && !empty($temp_data['round_pickup_time'])) {
            $temp_data['round_pickup_time'] = $this->convertTo24HourFormat($temp_data['round_pickup_time']);
        }

        try {
            $booking_data = CarBooking::create([
                'car_id' => $temp_data['car_id'],
                'user_id' => auth()->user()->id ?? null,
                'slug' => $temp_data['slug'] ?? $temp_data['car_slug'],
                'trx_id' => $trx_id,
                'payment_type' => $type,
                'phone' => $temp_data['phone'] ?? $temp_data['mobile'],
                'email' => $temp_data['email'] ?? $temp_data['credentials'],
                'location' => $temp_data['location'],
                'destination' => $temp_data['destination'],
                'trip_id' => generate_unique_code(),
                'pickup_time' => $temp_data['pickup_time'],
                'round_pickup_time' => $temp_data['round_pickup_time'],
                'amount' => $temp_data['total_rent'] ?? $temp_data['fees'],
                'charges' => $total_charge ?? 0,
                'distance' => $temp_data['distance'],
                'pickup_date' => $temp_data['pickup_date'],
                'round_pickup_date' => $temp_data['round_pickup_date'],
                'message' => $temp_data['message'] ?? '',
                'status' => 1,
            ]);


            $confirm_booking = CarBooking::with('cars')
                ->where('slug', $booking_data->slug)
                ->first();
            $temp_booking->delete();

            $this->bookingNotification($confirm_booking, $basic_setting);

        } catch (Exception $e) {
            throw new Exception(__("Something went wrong! Please try again."));
        }
    }
}

?>
