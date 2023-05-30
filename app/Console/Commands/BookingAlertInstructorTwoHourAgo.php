<?php

namespace App\Console\Commands;

use App\User;
use DateTime;
use Carbon\Carbon;
use App\Models\Contact;
use Illuminate\Support\Str;
use App\Models\Notification;
use App\Models\Courses\Course;
use Illuminate\Console\Command;
use App\Jobs\SendPushNotification;
use Illuminate\Support\Facades\Log;
use App\Notifications\BookingAlertFivePm;
use App\Notifications\BookingAlertTwoHourAgo;
use App\Models\BookingProcess\BookingProcesses;
use App\Models\BookingProcess\BookingProcessCourseDetails;
use App\Models\BookingProcess\BookingProcessInstructorDetails;

class BookingAlertInstructorTwoHourAgo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booking_alert_two_hour_ago:instructor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Email for Instructor Booking Alert 2 hour ago.';

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
        $current_date = date("Y-m-d H:i:s");
        
        $booking_ids_main = BookingProcesses::where('is_trash',0)->orderBy('id','desc');
        $booking_datas = BookingProcessCourseDetails::where('start_date','>',$current_date)
        ->select('id','booking_process_id','start_date','StartDate_Time')->get();
        
        $i = 0;  
        
        foreach ($booking_datas as $booking_data) {
            
            $start_date_time = new DateTime($booking_data->StartDate_Time);
            // dd($booking_data->StartDate_Time,$current_date,$start_date_time);

            $interval = Carbon::parse($current_date)->diff($start_date_time);
            
            $sender_id = null;
            if($interval->d<=1){   
                if($interval->h<=2){
                    $email_scheduler_type = 'TH';
                    $fleg = 'Two hour ago alert';
                    $instructor_data = BookingProcessInstructorDetails::where('booking_process_id',$booking_data->booking_process_id)->pluck('contact_id');

                    foreach ($instructor_data as $instructor) {
                        $check_notification_send = Notification::where("receiver_id",$instructor)
                            ->where('email_scheduler_type',$email_scheduler_type)
                            ->where('booking_process_id',$booking_data->booking_process_id)->count();

                        if($check_notification_send){continue;}

                        $booking_processes = BookingProcesses::find($booking_data->booking_process_id);
                        $booking_number = $booking_processes->booking_number;

                        $email_data['customer_name'] = Contact::where('id',$instructor)->select('id','first_name')->first()->toArray();  
                        
                        $course_details = BookingProcessCourseDetails::where('booking_process_id',$booking_data->booking_process_id)->first()->toArray();
                        
                        $course = Course::find($course_details['course_id']);
                        
                        $user_detail = User::where('contact_id',$instructor)
                        ->where('is_notification',1)
                        ->select('id','email','device_token','device_type','is_notification')
                        ->first();
                        
                        if($user_detail){
                            if($user_detail['is_notification']===1){
                            
                                $notifaction_data['course_id'] = $course_details['course_id']; 
                                $notifaction_data['booking_process_id'] = $booking_data->booking_process_id; 

                                $title = "Course  Alert : Two hour Ago";
                                $body = "Hello ".$email_data['customer_name']['first_name']." your course ".$course->name." ".$interval->h." hour ramain for start";

                                $notification = Notification::create(['sender_id'=>$sender_id,"receiver_id"=>$instructor,"type"=>18,'email_scheduler_type'=>$email_scheduler_type,'message'=>$body,'booking_process_id'=>$booking_data->booking_process_id]);

                                SendPushNotification::dispatch($user_detail['device_token'], $user_detail['device_type'], $title, $body, 18, $notifaction_data);
                            }
                            
                            $reset_token = Str::random(50);
                            $input['booking_token'] = $reset_token;
                            $user = BookingProcessInstructorDetails::where('booking_process_id',$booking_data->booking_process_id)
                            ->where('contact_id',$instructor)
                            ->update($input);
                            
                            $email_data['deep_link'] = url('/d/instructor/course-confirm?reset_token='.$reset_token."&booking_process_id=".$booking_data->booking_process_id."&instructor_id=".$instructor);

                            $course_name = $course->name;
                            // $user_detail->notify(new BookingAlertTwoHourAgo($email_data,$course_name,$booking_number,$fleg));
                            $user_detail->notify((new BookingAlertTwoHourAgo($email_data,$course_name,$booking_number,$fleg))->locale($user_detail->language_locale));
                        }
                    }
                }
            }
        }
        Log::info("booking_alert_two_hour_ago:instructor this cron is successfully executed.");
    }
}
