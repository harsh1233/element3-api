<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class TrainingMaterialNotifacation extends Notification implements ShouldQueue
{
    use Queueable;

    public $data;
    public $instructor_name;
    public $update_at;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($data,$instructor_name,$update_at)
    {
         $this->data = $data;
         $this->instructor_name = $instructor_name;
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
        return (new MailMessage)->subject(__('email_subject.assign_training_material.description',['instructor_name'=>$this->instructor_name ,'update_at' => date('h:i a', strtotime($this->update_at)) ]))->view(
            'email.customer.assign_teaching_material', ['data' => $this->data]
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
