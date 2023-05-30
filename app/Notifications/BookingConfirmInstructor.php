<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class BookingConfirmInstructor extends Notification implements ShouldQueue
{
    use Queueable;

    public $email_data;
    public $course_name;
    public $booking_number;
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

        return (new MailMessage)->subject(__('email_subject.confirm_booking.description_instructor', ['booking_number' => $this->booking_number, 'course_name'=> $this->course_name]))->view(
            'email.instructor.confirmation_of_booking_instructor',
            ['data' => $this->email_data]
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
