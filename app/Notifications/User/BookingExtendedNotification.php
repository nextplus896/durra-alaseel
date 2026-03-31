<?php

namespace App\Notifications\User;

use App\Models\CarBooking;
use App\Models\CarBookingTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingExtendedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected CarBooking $booking,
        protected CarBookingTransaction $extension
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $newReturnDate     = $this->extension->new_return_date->format('d M Y');
        $previousDate      = $this->extension->previous_return_date->format('d M Y');
        $extensionDays     = $this->extension->extension_days;
        $totalCost         = number_format($this->extension->total, 2);
        $carModel          = $this->booking->car_model;
        $trxId             = $this->booking->trx_id;

        return (new MailMessage)
            ->subject(__('Booking Extended - :trx', ['trx' => $trxId]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? __('Customer')]))
            ->line(__('Your car rental booking has been successfully extended.'))
            ->line(__('**Car:** :car', ['car' => $carModel]))
            ->line(__('**Booking Ref:** :trx', ['trx' => $trxId]))
            ->line(__('**Extended by:** :days day(s)', ['days' => $extensionDays]))
            ->line(__('**Previous return date:** :date', ['date' => $previousDate]))
            ->line(__('**New return date:** :date', ['date' => $newReturnDate]))
            ->line(__('**Amount charged:** :amount', ['amount' => $totalCost]))
            ->line(__('The amount has been deducted from your wallet balance.'))
            ->salutation(__('Thank you for choosing us!'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'booking_id'           => $this->booking->trip_id,
            'extension_id'         => $this->extension->id,
            'extension_days'       => $this->extension->extension_days,
            'new_return_date'      => $this->extension->new_return_date?->toDateString(),
            'previous_return_date' => $this->extension->previous_return_date?->toDateString(),
            'total_cost'           => $this->extension->total_cost,
        ];
    }
}
