<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class BookingAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $booking_data;
    public $course_name;
    public $booking_number;
    public $fleg;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($booking_data,$course_name,$booking_number,$fleg)
    {
        $this->booking_data = $booking_data;
        $this->course_name = $course_name;
        $this->booking_number = $booking_number;
        $this->fleg = $fleg;
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
        return (new MailMessage)->subject(__('email_subject.booking_alert.general_alert',['booking_number' => $this->booking_number, 'course_name'=> $this->course_name, 'fleg'=> $this->fleg ]))->view(
            'email.customer.booking_alert', ['booking_data' => $this->booking_data]
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
