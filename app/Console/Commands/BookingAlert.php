<?php

namespace App\Console\Commands;

use App\User;
use DateTime;
use App\Models\Contact;
use App\Models\Notification;
use App\Models\Courses\Course;
use Illuminate\Console\Command;
use App\Jobs\SendPushNotification;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use App\Models\BookingProcess\BookingProcesses;
use App\Notifications\BookingAlertNotification;
use App\Models\BookingProcess\BookingProcessCourseDetails;
use App\Models\BookingProcess\BookingProcessCustomerDetails;

class BookingAlert extends Command
{
    use Functions;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booking_alert:alertcustomers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Email for Booking Alert customers';

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
        //Log::error("This is a test log");   

        $booking_processes_ids = BookingProcesses::query();
        $booking_processes_ids = $booking_processes_ids->where('is_trash',0)->orderBy('id','desc');
        $booking_processes_ids1 = $booking_processes_ids->pluck('id');
        foreach ($booking_processes_ids1 as $booking_id) {
            $customer_details = BookingProcessCustomerDetails::where('booking_process_id',$booking_id)
            ->where('is_cancelled', false)
            ->select('customer_id','StartDate_Time','EndDate_Time')->get();
            $current_date = date("Y-m-d H:i:s");
            //dd($booking_id);
            
            foreach ($customer_details as $customer_detail) {
                    /* try
                    { */
                        if($customer_detail->StartDate_Time>=$current_date){
                            if($customer_detail->StartDate_Time || $customer_detail->EndDate_Time){
                            $start_date = explode(" ", $customer_detail->StartDate_Time);  
                            $booking_data['start_date'] = $start_date[0];                     
                            $booking_data['start_time'] = $start_date[1]; 
                            $end_date = explode(" ", $customer_detail->EndDate_Time);  
                            $booking_data['end_date'] = $end_date[0];  
                            $booking_data['end_time'] = $end_date[1]; 
                        
                        
                            $tdate = $customer_detail->StartDate_Time;
                            $datetime1 = new DateTime($current_date);
                            $datetime2 = new DateTime($tdate);
                            
                            $interval = $datetime1->diff($datetime2);
                            $days = $interval->format('%a') + 1;
                            $hours = $interval->format('%h');
                            
                            //dd($datetime1,$customer_detail->StartDate_Time,$customer_detail->EndDate_Time,$days,$hours,$interval);
                        if(($days==28) || ($days==7) || ($days==0 && $hours=23)){
                            
                            if($days==28){
                                $email_scheduler_type = 'FW';
                            }else if($days==7){
                                $email_scheduler_type = 'OW';
                            }elseif($days==0 && $hours=23){
                                $email_scheduler_type = 'TF';
                            }
                            
                            $user_detail = User::where('contact_id',$customer_detail->customer_id)
                            ->where('is_notification',1)
                            ->select('id','email','device_token','device_type')
                            ->first();

                            $contact = Contact::find($customer_detail->customer_id);
                            
                            $customer_details = BookingProcessCourseDetails::where('booking_process_id',$booking_id)
                            ->select('course_id','meeting_point','course_type')->first();

                            $booking_datail = BookingProcesses::where('id',$booking_id)
                            ->first();

                            //Email send data
                            $booking_data['user_detail'] = $user_detail;
                            $booking_data['user_detail']['name'] = $contact->first_name;                            
                            $booking_data['customer_details'] = $customer_details;
                            $booking_data['booking_number'] = $booking_datail->booking_number;
                            //

                            $check_notification_send = Notification::where("receiver_id",$customer_detail->customer_id)
                            ->where('email_scheduler_type',$email_scheduler_type)
                            ->where('booking_process_id',$booking_id)->count();

                            if($check_notification_send){continue;}

                            //Notification send data
                            
                                    $course = Course::find($customer_details->course_id);
                                    $notifaction_data['course_id'] = $customer_details->course_id; 
                                    $notifaction_data['booking_process_id'] = $booking_id; 
                                    $title = "Course Alert";
                                    $body = "Hello ".$contact->first_name." your course ".$course->name." is starting of ".$days." days ".$hours." hour ramain";

                                    /*
                                    dd(__('notifications.booking_alert', [
                                    'user_name'=>'Parth sir',
                                    'course_name'=>'FLANO',
                                    'count'=>'5',
                                    'unit'=>'hours',
                                    ]));
                                    */ 
                                    //==

                                    $sender_id = null;
        
                                    //This is only write log file which customer send the email
                                    if($email_scheduler_type == 'FW'){
                                        $fleg = 'Four week before alert';
                                    }else if($email_scheduler_type == 'OW'){
                                        $fleg = 'One week before alert';
                                    }elseif($email_scheduler_type == 'TF'){
                                        $fleg = 'Twenty four hour before alert';
                                    }
                                    Log::info("No of Days ".$days." and houre ".$hours);

                                    Log::info("Booking Alert for ".$fleg." Send to : ".$user_detail['email']);
                                    //==
                                    
                                    if($user_detail){
                                        $notification = Notification::create(['sender_id'=>$sender_id,"receiver_id"=>$customer_detail->customer_id,"type"=>8,'email_scheduler_type'=>$email_scheduler_type,'message'=>$body,'booking_process_id'=>$booking_id]);

                                        SendPushNotification::dispatch($user_detail['device_token'],$user_detail['device_type'],$title,$body,8,$notifaction_data);
                                        
                                        /* $this->push_notification($user_detail['device_token'],$user_detail['device_type'],$title,$body,8,$notifaction_data); */
                                        $course_name = $course->name;
                                        // $user_detail->notify(new BookingAlertNotification($booking_data,$course_name,$booking_datail->booking_number,$fleg));
                                        $user_detail->notify((new BookingAlertNotification($booking_data,$course_name,$booking_datail->booking_number,$fleg))->locale($user_detail->language_locale));
                                        /* if(config('constants.MAIL_HOST') == 'smtp.mailtrap.io'){
                                            sleep(1);
                                        } */
                                    }
                                }  
                            }
                        }                      
                    /* }
                    catch (\Exception $e)
                    {
                        Log::error("An error occurs in send booking alert");   
                    } */
            }   
        }
        Log::info("booking_alert:alertcustomers this cron is successfully executed.");
    }
}
