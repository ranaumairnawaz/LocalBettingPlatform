<?php

namespace App\Notifications;

use App\Models\TournamentMatch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class TournamentReadyCheckNotification extends Notification
{
    use Queueable;

    public function __construct(public TournamentMatch $match) {}

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, object $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('⚔️ Ready Check!')
            ->icon('/images/logo.png')
            ->body("Your tournament match is starting now! Log in to confirm you're ready.")
            ->action('Play Now', 'play')
            ->data(['match_id' => $this->match->id, 'url' => '/tournaments/match/' . $this->match->id])
            ->vibrate([200, 100, 200]);
    }
}
