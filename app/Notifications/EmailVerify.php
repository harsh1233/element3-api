<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class EmailVerify extends Notification implements ShouldQueue
{
    use Queueable;

    public $token;
    public $contact_name;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($token,$contact_name)
    {
       $this->token = $token;
       $this->contact_name = $contact_name;
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
                    ->subject("NoReply: Element3 Email Verification")
                    ->line('You are receiving this email because we received a email verify status for your account.')
                    ->action('Verify Email', url('/email-verify/'.$this->token)); */
                    // ->line('If you did not request a password reset, no further action is required.');

                    return (new MailMessage)->subject('NoReply: Element3 Email Verification')->view(
                        'email.customer.email_verify',
                        ['token' => $this->token,'conatct_name' => $this->contact_name]
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
