<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class InvitationCode extends Notification implements ShouldQueue
{
    use Queueable;
    public $code;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($code)
    {
        $this->code = $code;
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
        sleep(5);
                    /* return (new MailMessage)
                    ->subject("Invitation from Element3")
                    ->line('You are receiving this email because we created new account with your email address')
                    ->line('Your registration code is '.$this->code)
                    ->line('Your code will expire in 2 days')
                    ->line('Thank you for using our application!'); */

                    return (new MailMessage)->subject('Invitation from Element3 to join as an instructor!')->view(
                        'email.customer.invitation_code',
                        ['code' => $this->code]
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
