<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class CreateMultipleBooking extends Notification implements ShouldQueue
{
     use Queueable;

    public $email_data;
    public $course_name;
    public $booking_number;
    // public $update_at;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($email_data, $course_name, $booking_number)
    {
        $this->email_data = $email_data;
        $this->course_name = $course_name;
        $this->booking_number = $booking_number;
        // $this->update_at = $update_at;
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
        // to be removed when sending limits increase
        sleep(5);

         return (new MailMessage)->subject(__('email_subject.multiple_booking.description',['booking_number' => $this->booking_number, 'course_name'=> $this->course_name]))->view(
            'email.book2ski.multiple_booking',
            ['data' => $this->email_data, 'course_name' => $this->course_name]
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
