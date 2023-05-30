<?php

namespace App\Console\Commands;

use App\User;
use App\Models\Notification;
use Illuminate\Console\Command;
use App\Jobs\SendPushNotification;
use Illuminate\Support\Facades\Log;
use App\Models\BookingProcess\BookingProcessCourseDetails;
use App\Models\BookingProcess\BookingProcessInstructorDetails;

class BookingStartNotificationAlert extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booking_start_alert:alertinstructors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for send notification to when booking will be start and remain 10 min';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Log::info("booking_start_alert:alertinstructors this cron is start for execute.");
        $ten_min_after = date('Y-m-d H:i:s', strtotime('+10 min'));
        $current_date_time = date('Y-m-d H:i:s');

        $notification_type = 28;
        $booking_course_detail = BookingProcessCourseDetails::where('StartDate_Time', '>=', $current_date_time)
        ->where('StartDate_Time' , '<=' , $ten_min_after)->get();

        foreach($booking_course_detail as $booking_course){
            $booking_instructor_detail = BookingProcessInstructorDetails::where('booking_process_id', $booking_course->booking_process_id)
            ->pluck('contact_id');

            foreach($booking_instructor_detail as $instructor){
                $check_notification_send = Notification::where("receiver_id",$instructor)
                ->where('type', 28)
                ->where('booking_process_id',$booking_course['booking_process_id'])->count();
                
                if($check_notification_send){continue;}
                
                $sender_id = null;
                $user_detail = User::where('contact_id',$instructor)
                ->where('is_notification',1)
                ->select('id','name','email','device_token','device_type','is_notification')
                ->first();

                if($user_detail){
                    if($user_detail['is_notification']===1){
                        $notifaction_data['course_id'] = $booking_course['course_id']; 
                        $notifaction_data['booking_process_id'] = $booking_course['booking_process_id']; 

                        $title = "Don’t forget to start your activity recording";
                        $body = "Hello ".$user_detail['name'].", Don’t forget to start your activity recording.";

                        $notification = Notification::create(['sender_id'=>$sender_id,"receiver_id"=>$instructor,"type"=>28,'message'=>$body,'booking_process_id'=>$booking_course['booking_process_id']]);

                        SendPushNotification::dispatch($user_detail['device_token'], $user_detail['device_type'], $title, $body, 28, $notifaction_data);
                    }
                }
            }
        }
        Log::info("booking_start_alert:alertinstructors this cron is successfully executed.");
    }
}
