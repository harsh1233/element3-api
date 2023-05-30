<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AdminAlert extends Notification
{
    use Queueable;

    public $alert_details;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($alert_details)
    {
        $this->alert_details = $alert_details;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)->subject(__('email_subject.admin_alert',['created_at' => date('h:i a', strtotime($this->alert_details['created_at'])) ]))->view(
            'email.admin_alert_view', ['alert_details' => $this->alert_details]
        );
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
