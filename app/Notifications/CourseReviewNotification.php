<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class CourseReviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $data;
    public $booking_number;
    // public $update_at;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($data,$booking_number)
    {
        $this->data = $data;
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
        sleep(5);
        return (new MailMessage)->subject(__('email_subject.course_review.description',['booking_number' => $this->booking_number]))->view(
            'email.customer.course_review', ['data' => $this->data]
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
