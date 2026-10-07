<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class DepositApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public float $amount) {}

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, object $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('💰 Deposit Approved!')
            ->icon('/images/logo.png')
            ->body("Your deposit of PKR " . number_format($this->amount, 2) . " has been approved and credited to your wallet.")
            ->action('View Wallet', 'wallet')
            ->data(['url' => '/wallet'])
            ->vibrate([100]);
    }
}
