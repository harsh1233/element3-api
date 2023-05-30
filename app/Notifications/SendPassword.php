<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SendPassword extends Notification implements ShouldQueue
{
    use Queueable;
    public $password;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($password)
    {
        $this->password = $password;
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
                    ->subject("Your Account for Element3 CRM")
                    ->line('You are receiving this email because we created new account with your email address')
                    ->line('Your temporary password is '.$this->password)
                    ->line('Thank you for using our application!'); */

                    return (new MailMessage)->subject('Your Element3 temparory password')->view(
                    'email.customer.send_password',
                        ['password' => $this->password]
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
