<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class PasswordResetEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public $token;
    public $user;
    public $is_app_user;
    public $update_at;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($token,$user,$is_app_user,$update_at)
    {
        $this->token = $token;
        $this->user = $user;
        $this->is_app_user = $is_app_user;
        $this->update_at = $update_at;
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
                   /*  return (new MailMessage)
                    ->subject("Reset Password")
                    ->line('You are receiving this email because we received a password reset request for your account.')
                    ->action('Reset Password', config('constants.front_base_url').'/reset-password?token='.$this->token.'&email='.$this->user->email.'&is_app_user='.$this->user->is_app_user)
                    ->line('If you did not request a password reset, no further action is required.'); */
                    
                    return (new MailMessage)->subject(__('email_subject.password_reset.description',['update_at' =>         date('h:i a', strtotime($this->update_at)) ]))
                        ->view('email.customer.password_reset',
                        ['token' => $this->token,'email' => $this->user->email ,'is_app_user' => $this->user->is_app_user]
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
