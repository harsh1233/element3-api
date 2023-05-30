<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class BookingAlertTwoHourAgo extends Notification implements ShouldQueue
{
    use Queueable;

    public $email_data;
    public $course_name;
    public $booking_number;
    public $fleg;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($email_data,$course_name,$booking_number,$fleg)
    {
        $this->email_data = $email_data;        
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
         // to be removed when sending limits increase
        sleep(5);
        return (new MailMessage)->subject(__('email_subject.booking_alert.two_hour_ago',['booking_number' => $this->booking_number, 'course_name'=> $this->course_name, 'fleg'=> $this->fleg ]))->view(
            'email.instructor.booking_alert_two_hour', ['data' => $this->email_data]
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
