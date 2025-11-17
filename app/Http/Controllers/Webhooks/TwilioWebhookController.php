<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\TwilioMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TwilioWebhookController extends Controller
{
    /**
     * Handle Twilio status callback webhook
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function statusCallback(Request $request)
    {
        try {
            $messageSid = $request->input('MessageSid') ?? $request->input('SmsSid');
            $status = $request->input('MessageStatus') ?? $request->input('SmsStatus');
            $to = $request->input('To');
            $from = $request->input('From');
            $errorCode = $request->input('ErrorCode');
            $errorMessage = $request->input('ErrorMessage');

            if (!$messageSid) {
                Log::warning('Twilio webhook missing MessageSid', $request->all());
                return response()->json(['status' => 'error', 'message' => 'Missing MessageSid'], 400);
            }

            // Determine channel based on 'From' or 'To' field
            $channel = 'sms';
            if (str_contains($from, 'whatsapp:') || str_contains($to, 'whatsapp:')) {
                $channel = 'whatsapp';
            }

            // Update or create message record
            $message = TwilioMessage::updateOrCreate(
                ['message_sid' => $messageSid],
                [
                    'account_sid' => $request->input('AccountSid'),
                    'to' => $to,
                    'from' => $from,
                    'channel' => $channel,
                    'status' => $status,
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                    'body' => $request->input('Body'),
                    'metadata' => [
                        'webhook_data' => $request->except(['_token']),
                    ],
                ]
            );

            Log::info('Twilio webhook processed', [
                'message_sid' => $messageSid,
                'status' => $status,
                'channel' => $channel,
            ]);

            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            Log::error('Twilio webhook processing failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle Twilio pricing callback (optional)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pricingCallback(Request $request)
    {
        try {
            $messageSid = $request->input('MessageSid');
            $price = $request->input('Price');
            $priceUnit = $request->input('PriceUnit');

            if ($messageSid && $price) {
                TwilioMessage::where('message_sid', $messageSid)->update([
                    'price' => abs((float)$price), // Twilio returns negative values
                    'price_unit' => $priceUnit,
                ]);

                Log::info('Twilio pricing updated', [
                    'message_sid' => $messageSid,
                    'price' => $price,
                    'unit' => $priceUnit,
                ]);
            }

            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            Log::error('Twilio pricing webhook failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }
}
