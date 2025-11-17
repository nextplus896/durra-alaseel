<?php

namespace App\Services;

use App\Models\PhoneVerification;
use App\Models\TwilioMessage;
use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TwilioService
{
    protected $client;
    protected $verifySid;
    protected $fromNumber;
    protected $whatsappFrom;

    public function __construct()
    {
        $accountSid = config('services.twilio.account_sid');
        $authToken = config('services.twilio.auth_token');

        $this->verifySid = config('services.twilio.verify_sid');
        $this->fromNumber = config('services.twilio.from_number');
        $this->whatsappFrom = config('services.twilio.whatsapp_from');

        if ($accountSid && $authToken) {
            $this->client = new Client($accountSid, $authToken);
        }
    }

    /**
     * Send OTP via SMS using Twilio Verify API
     */
    public function sendOtpSms(string $phone, string $locale = 'en'): array
    {
        return $this->sendOtp($phone, 'sms', $locale);
    }

    /**
     * Send OTP via WhatsApp using Twilio Verify API or Direct Messaging
     */
    public function sendOtpWhatsApp(string $phone, string $locale = 'en'): array
    {
        // Check if using direct messaging (for templates) or Verify API
        $useDirectMessaging = config('services.twilio.use_direct_messaging', false);

        if ($useDirectMessaging) {
            return $this->sendWhatsAppDirect($phone, $locale);
        }

        return $this->sendOtp($phone, 'whatsapp', $locale);
    }

    /**
     * Send WhatsApp message directly with template
     */
    protected function sendWhatsAppDirect(string $phone, string $locale = 'en'): array
    {
        try {
            if (!$this->client) {
                throw new \Exception('Twilio credentials not configured');
            }

            // Format phone number
            $formattedPhone = $this->formatPhoneNumber($phone);

            // Generate OTP code
            $otpCode = $this->generateOtpCode();

            // Create verification record
            $verification = PhoneVerification::create([
                'phone' => $formattedPhone,
                'channel' => 'whatsapp',
                'status' => 'pending',
                'attempts' => 0,
                'expires_at' => Carbon::now()->addMinutes(10),
                'metadata' => [
                    'locale' => $locale,
                    'ip' => request()->ip(),
                    'otp_code' => $otpCode,
                ],
            ]);

            // Send WhatsApp message with template
            $message = $this->client->messages->create(
                "whatsapp:$formattedPhone",
                [
                    'from' => $this->whatsappFrom,
                    'body' => "Your verification code is: $otpCode. Valid for 10 minutes.",
                ]
            );

            // Log the message
            TwilioMessage::create([
                'message_sid' => $message->sid,
                'account_sid' => $message->accountSid,
                'to' => $message->to,
                'from' => $message->from,
                'channel' => 'whatsapp',
                'direction' => 'outbound',
                'body' => $message->body,
                'status' => $message->status,
                'verification_id' => $verification->id,
            ]);

            Log::info('WhatsApp OTP sent directly', [
                'phone' => $formattedPhone,
                'sid' => $message->sid,
                'status' => $message->status,
            ]);

            return [
                'success' => true,
                'verification_id' => $verification->id,
                'status' => $message->status,
                'to' => $message->to,
                'channel' => 'whatsapp',
            ];
        } catch (TwilioException $e) {
            Log::error('WhatsApp direct send failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp send failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send OTP using Twilio Verify API
     */
    protected function sendOtp(string $phone, string $channel = 'sms', string $locale = 'en'): array
    {
        try {
            if (!$this->client || !$this->verifySid) {
                throw new \Exception('Twilio credentials not configured');
            }

            // Format phone number to E.164
            $formattedPhone = $this->formatPhoneNumber($phone);

            // Create verification record
            $verification = PhoneVerification::create([
                'phone' => $formattedPhone,
                'channel' => $channel,
                'status' => 'pending',
                'attempts' => 0,
                'expires_at' => Carbon::now()->addMinutes(10),
                'metadata' => [
                    'locale' => $locale,
                    'ip' => request()->ip(),
                ],
            ]);

            // Send verification via Twilio Verify API
            $twilioVerification = $this->client->verify->v2
                ->services($this->verifySid)
                ->verifications
                ->create(
                    $formattedPhone,
                    $channel,
                    [
                        'locale' => $locale,
                    ]
                );

            // Update verification record
            $verification->update([
                'verification_sid' => $twilioVerification->sid,
                'status' => $twilioVerification->status,
            ]);

            Log::info('Twilio OTP sent', [
                'phone' => $formattedPhone,
                'channel' => $channel,
                'sid' => $twilioVerification->sid,
            ]);

            return [
                'success' => true,
                'verification_id' => $verification->id,
                'status' => $twilioVerification->status,
                'to' => $twilioVerification->to,
                'channel' => $channel,
                'valid' => $twilioVerification->valid,
            ];
        } catch (TwilioException $e) {
            Log::error('Twilio OTP send failed', [
                'phone' => $phone,
                'channel' => $channel,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        } catch (\Exception $e) {
            Log::error('OTP send failed', [
                'phone' => $phone,
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify OTP code - supports both Verify API and Direct Messaging modes
     */
    public function verifyOtp(string $phone, string $code): array
    {
        try {
            if (!$this->client) {
                throw new \Exception('Twilio credentials not configured');
            }

            // Format phone number to E.164
            $formattedPhone = $this->formatPhoneNumber($phone);

            // Find the latest pending verification
            $verification = PhoneVerification::where('phone', $formattedPhone)
                ->where('status', 'pending')
                ->whereNull('verified_at')
                ->where('expires_at', '>', Carbon::now())
                ->latest()
                ->first();

            if (!$verification) {
                return [
                    'success' => false,
                    'error' => 'No pending verification found or verification expired',
                ];
            }

            // Increment attempts
            $verification->increment('attempts');

            // Check if using direct messaging (WhatsApp with custom OTP)
            $useDirectMessaging = config('services.twilio.use_direct_messaging', false);

            if ($useDirectMessaging && $verification->channel === 'whatsapp') {
                // Verify against stored OTP code in metadata
                $storedOtpCode = $verification->metadata['otp_code'] ?? null;

                if (!$storedOtpCode) {
                    return [
                        'success' => false,
                        'error' => 'OTP code not found for this verification',
                    ];
                }

                if ($code !== $storedOtpCode) {
                    Log::warning('WhatsApp OTP verification failed - code mismatch', [
                        'phone' => $formattedPhone,
                        'attempts' => $verification->attempts,
                    ]);

                    return [
                        'success' => false,
                        'error' => 'Invalid verification code',
                    ];
                }

                // Code matches - update verification
                $verification->update([
                    'status' => 'approved',
                    'verified_at' => Carbon::now(),
                ]);

                Log::info('WhatsApp OTP verified successfully', [
                    'phone' => $formattedPhone,
                    'verification_id' => $verification->id,
                ]);

                return [
                    'success' => true,
                    'status' => 'approved',
                    'verification_id' => $verification->id,
                ];
            }

            // Otherwise use Twilio Verify API (for SMS or Verify API mode)
            if (!$this->verifySid) {
                throw new \Exception('Twilio Verify Service ID not configured');
            }

            // Verify code via Twilio
            $verificationCheck = $this->client->verify->v2
                ->services($this->verifySid)
                ->verificationChecks
                ->create([
                    'to' => $formattedPhone,
                    'code' => $code,
                ]);

            // Update verification status
            if ($verificationCheck->status === 'approved') {
                $verification->update([
                    'status' => 'approved',
                    'verified_at' => Carbon::now(),
                ]);

                Log::info('Twilio OTP verified', [
                    'phone' => $formattedPhone,
                    'sid' => $verificationCheck->sid,
                ]);

                return [
                    'success' => true,
                    'status' => 'approved',
                    'verification_id' => $verification->id,
                    'valid' => $verificationCheck->valid,
                ];
            }

            Log::warning('Twilio OTP verification failed', [
                'phone' => $formattedPhone,
                'status' => $verificationCheck->status,
            ]);

            return [
                'success' => false,
                'error' => 'Invalid verification code',
                'status' => $verificationCheck->status,
            ];
        } catch (TwilioException $e) {
            Log::error('Twilio OTP verify failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        } catch (\Exception $e) {
            Log::error('OTP verify failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format phone number to E.164 format
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove any non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Add + if not present
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        return $phone;
    }

    /**
     * Generate a random 6-digit OTP code
     */
    protected function generateOtpCode(): string
    {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    /**
     * Get current Twilio account balance
     */
    public function getAccountBalance(): array
    {
        try {
            if (!$this->client) {
                throw new \Exception('Twilio credentials not configured');
            }

            // Get the account SID from config
            $accountSid = config('services.twilio.account_sid');

            // Fetch the account details which includes balance
            $account = $this->client->api->v2010->accounts($accountSid)->fetch();

            return [
                'success' => true,
                'balance' => round(abs((float)$account->balance), 2),
                'currency' => 'USD',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch Twilio balance', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'balance' => 0,
                'currency' => 'USD',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get Twilio usage statistics
     */
    public function getUsageStatistics(string $startDate = null, string $endDate = null): array
    {
        $query = TwilioMessage::query();

        if ($startDate) {
            $query->where('created_at', '>=', Carbon::parse($startDate));
        }

        if ($endDate) {
            $query->where('created_at', '<=', Carbon::parse($endDate));
        }

        $smsStats = (clone $query)->where('channel', 'sms')->selectRaw('
            COUNT(*) as total_count,
            SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered_count,
            SUM(CASE WHEN status IN ("failed", "undelivered") THEN 1 ELSE 0 END) as failed_count,
            SUM(price) as total_cost
        ')->first();

        $whatsappStats = (clone $query)->where('channel', 'whatsapp')->selectRaw('
            COUNT(*) as total_count,
            SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered_count,
            SUM(CASE WHEN status IN ("failed", "undelivered") THEN 1 ELSE 0 END) as failed_count,
            SUM(price) as total_cost
        ')->first();

        $recentMessages = TwilioMessage::latest()
            ->limit(50)
            ->get();

        // Get current balance
        $balanceData = $this->getAccountBalance();

        return [
            'sms' => [
                'total' => $smsStats->total_count ?? 0,
                'delivered' => $smsStats->delivered_count ?? 0,
                'failed' => $smsStats->failed_count ?? 0,
                'cost' => round($smsStats->total_cost ?? 0, 2),
                'delivery_rate' => $smsStats->total_count > 0
                    ? round(($smsStats->delivered_count / $smsStats->total_count) * 100, 2)
                    : 0,
            ],
            'whatsapp' => [
                'total' => $whatsappStats->total_count ?? 0,
                'delivered' => $whatsappStats->delivered_count ?? 0,
                'failed' => $whatsappStats->failed_count ?? 0,
                'cost' => round($whatsappStats->total_cost ?? 0, 2),
                'delivery_rate' => $whatsappStats->total_count > 0
                    ? round(($whatsappStats->delivered_count / $whatsappStats->total_count) * 100, 2)
                    : 0,
            ],
            'recent_messages' => $recentMessages,
            'total_cost' => round(($smsStats->total_cost ?? 0) + ($whatsappStats->total_cost ?? 0), 2),
            'balance' => $balanceData['balance'] ?? 0,
            'balance_currency' => $balanceData['currency'] ?? 'USD',
        ];
    }
}
