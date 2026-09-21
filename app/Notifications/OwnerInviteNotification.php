<?php

namespace App\Notifications;

use App\Models\Restaurant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class OwnerInviteNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Restaurant $restaurant,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $hours = (int) config('sowarder.owner_invite_expiry_hours', 72);

        $url = URL::temporarySignedRoute(
            'owner-invite.show',
            now()->addHours($hours),
            ['user' => $notifiable->getKey()],
        );

        return (new MailMessage)
            ->subject('Set up your Sowarder restaurant admin')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('You have been invited to manage '.$this->restaurant->name.' on Sowarder.')
            ->line('Use the button below to set your admin panel password. This link expires in '.$hours.' hours.')
            ->action('Set password', $url)
            ->line('After setup, sign in at /app/'.$this->restaurant->slug.'.');
    }
}
