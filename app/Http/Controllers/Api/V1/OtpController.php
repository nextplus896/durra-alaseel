<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TwilioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OtpController extends Controller
{
    protected $twilioService;

    public function __construct(TwilioService $twilioService)
    {
        $this->twilioService = $twilioService;
    }

    /**
     * Request OTP via SMS or WhatsApp
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function request(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'regex:/^\+?[1-9]\d{1,14}$/'],
            'channel' => ['required', 'string', Rule::in(['sms', 'whatsapp'])],
            'locale' => ['nullable', 'string', Rule::in(['en', 'ar', 'es', 'fr'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $phone = $request->input('phone');
        $channel = $request->input('channel', 'sms');
        $locale = $request->input('locale', 'en');

        // Send OTP based on channel
        if ($channel === 'whatsapp') {
            $result = $this->twilioService->sendOtpWhatsApp($phone, $locale);
        } else {
            $result = $this->twilioService->sendOtpSms($phone, $locale);
        }

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully',
                'data' => [
                    'verification_id' => $result['verification_id'],
                    'channel' => $result['channel'],
                    'to' => $result['to'],
                    'status' => $result['status'],
                ],
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error'] ?? 'Failed to send OTP',
            'error_code' => $result['code'] ?? null,
        ], 400);
    }

    /**
     * Verify OTP code
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'regex:/^\+?[1-9]\d{1,14}$/'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $phone = $request->input('phone');
        $code = $request->input('code');

        $result = $this->twilioService->verifyOtp($phone, $code);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Phone verified successfully',
                'data' => [
                    'verification_id' => $result['verification_id'],
                    'status' => $result['status'],
                ],
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error'] ?? 'Invalid verification code',
            'error_code' => $result['code'] ?? null,
        ], 400);
    }
}
