<?php

namespace App\Notifications\User;

use App\Http\Helpers\PushNotificationHelper;
use App\Models\Admin\BasicSettings;
use App\Models\UserNotification;
use App\Constants\NotificationConst;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class WalletDeductedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected float  $amount,
        protected float  $newBalance,
        protected string $reason = '',
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'    => 'wallet_deducted',
            'amount'  => $this->amount,
            'balance' => $this->newBalance,
            'reason'  => $this->reason,
            'message' => __(':amount SAR deducted from your wallet', ['amount' => number_format($this->amount, 2)], 'ar'),
        ];
    }

    public function afterCommit(): bool
    {
        return true;
    }

    public function created($notifiable): void
    {
        $this->sendPushAndInApp($notifiable);
    }

    protected function sendPushAndInApp($notifiable): void
    {
        $content = [
            'title'   => __('Wallet Deducted', [], 'ar'),
            'message' => __(':amount SAR deducted from your wallet. Remaining balance: :balance SAR', [
                'amount'  => number_format($this->amount, 2),
                'balance' => number_format($this->newBalance, 2),
            ], 'ar'),
            'time'  => Carbon::now()->diffForHumans(),
            'image' => files_asset_path('profile-default'),
        ];

        try {
            UserNotification::create([
                'type'    => NotificationConst::WALLET_DEDUCTED ?? 'WALLET_DEDUCTED',
                'user_id' => $notifiable->id,
                'message' => $content,
            ]);
        } catch (\Throwable $e) {
            Log::warning('WalletDeductedNotification: UserNotification create failed', ['error' => $e->getMessage()]);
        }

        try {
            $settings = BasicSettings::first();
            if ($settings && $settings->push_notification) {
                (new PushNotificationHelper())
                    ->prepare(
                        [$notifiable->id],
                        [
                            'title'     => $content['title'],
                            'desc'      => $content['message'],
                            'user_type' => 'user',
                        ],
                    )
                    ->send();
            }
        } catch (\Throwable $e) {
            Log::warning('WalletDeductedNotification: Push failed', ['error' => $e->getMessage()]);
        }
    }
}
