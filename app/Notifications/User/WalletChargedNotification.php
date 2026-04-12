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

class WalletChargedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected float $amount,
        protected float $newBalance,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'    => 'wallet_charged',
            'amount'  => $this->amount,
            'balance' => $this->newBalance,
            'message' => __('Wallet topped up by :amount SAR', ['amount' => number_format($this->amount, 2)], 'ar'),
        ];
    }

    /**
     * After the notification is sent, also create an in-app UserNotification
     * and fire a push notification (consistent with existing codebase pattern).
     */
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
            'title'   => __('Wallet Recharged', [], 'ar'),
            'message' => __('Your wallet has been charged with :amount SAR. New balance: :balance SAR', [
                'amount'  => number_format($this->amount, 2),
                'balance' => number_format($this->newBalance, 2),
            ], 'ar'),
            'time'  => Carbon::now()->diffForHumans(),
            'image' => files_asset_path('profile-default'),
        ];

        try {
            UserNotification::create([
                'type'    => NotificationConst::WALLET_CHARGED ?? 'WALLET_CHARGED',
                'user_id' => $notifiable->id,
                'message' => $content,
            ]);
        } catch (\Throwable $e) {
            Log::warning('WalletChargedNotification: UserNotification create failed', ['error' => $e->getMessage()]);
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
            Log::warning('WalletChargedNotification: Push failed', ['error' => $e->getMessage()]);
        }
    }
}
