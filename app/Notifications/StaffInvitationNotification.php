<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffInvitationNotification extends Notification
{
    public function __construct(public string $setupUrl) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Your African Ranked author invitation')->line('You have been invited to the African Ranked editorial workspace.')->action('Set up your account', $this->setupUrl)->line('This link expires in 24 hours and can be used once. If this invitation was unexpected, ignore it.');
    }
}
