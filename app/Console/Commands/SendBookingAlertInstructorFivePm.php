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
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use App\Notifications\BookingAlertFivePm;
use App\Models\BookingProcess\BookingProcesses;
use App\Models\BookingProcess\BookingProcessCourseDetails;
use App\Models\BookingProcess\BookingProcessCustomerDetails;
use App\Models\BookingProcess\BookingProcessInstructorDetails;

class SendBookingAlertInstructorFivePm extends Command
{
    use Functions;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send_booking_alert_five_pm:instructor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Email for Instructor Booking Alert one day ago at 5 PM';

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
            
            // $sender_id = null;
            $customer_name = array();
            if($interval->d<=1){                
            if($interval->h<=18){
                    $email_scheduler_type = 'FP';
                    
                    $instructor_data = BookingProcessInstructorDetails::where('booking_process_id',$booking_data->booking_process_id)->pluck('contact_id');

                    foreach ($instructor_data as $instructor) {
                        $check_notification_send = Notification::where("receiver_id",$instructor)
                            ->where('email_scheduler_type',$email_scheduler_type)
                            ->where('booking_process_id',$booking_data->booking_process_id)->count();

                        if($check_notification_send){continue;}

                        $this->sendNotificationCourseConfirm($booking_data->booking_process_id,$instructor,$interval,$email_scheduler_type);
                    }
                }
            }
        }
        Log::info("send_booking_alert_five_pm:instructor this cron is successfully executed.");
    }
}
