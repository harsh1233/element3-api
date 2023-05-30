<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\Notification;
use App\Models\Courses\Course;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Notifications\CourseReviewCustomer;
use App\Models\BookingProcess\BookingProcesses;
use App\Notifications\CourseReviewNotification;
use App\Models\BookingProcess\BookingProcessCourseDetails;
use App\Models\BookingProcess\BookingProcessCustomerDetails;

class ReviewCourseCustomer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'review_course:customers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Email for Review the course of customer';

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
            $course_details = BookingProcessCourseDetails::where('booking_process_id',$booking_id)
            ->first();

            $course = Course::where('id', $course_details->course_id)->first();
            $course_name = $course->name;

            $current_date = date("Y-m-d H:i:s");
            $current_date1 = explode(" ", $current_date);  
            $current_date = $current_date1[0];                     
            $current_time = $current_date1[1]; 
            
            if($course_details['start_date']<$current_date && $current_date>$course_details['end_date']){
        
                $customer_details = BookingProcessCustomerDetails::where('booking_process_id',$booking_id)
                ->where('is_cancelled', false)
                ->where('is_new_invoice', false)
                ->select('customer_id','StartDate_Time','EndDate_Time')->get();
                $current_date = date("Y-m-d H:i:s");
                
                $booking_processes = BookingProcesses::find($booking_id);
                $booking_number=$booking_processes->booking_number;

                $update_at = date('H:i:s');

                foreach ($customer_details as $customer_detail) {
                        /* try
                        { */
                                $email_scheduler_type = 'RC';

                                $check_notification_send = Notification::where("receiver_id",$customer_detail->customer_id)->where('email_scheduler_type',$email_scheduler_type)
                                    ->where('booking_process_id',$booking_id)->count();

                                if($check_notification_send){continue;}

                                $contact = Contact::where('id',$customer_detail->customer_id)->first();
                                // $customer = $contact->email;  

                                $title = "Customer Course Review";
                                $body = "Please Share Your Past Course Review ,we send email for share a review";
                                $type = 20;

                                $data['customer_name'] = $contact->first_name;
                                $data['deep_link'] =  url('/d/instructor/review-customer?booking_process_id='.$booking_id.'&course_id='.$course_details->course_id);

                                $notification = Notification::create(["receiver_id"=>$customer_detail->customer_id,"type"=>$type,'email_scheduler_type'=>$email_scheduler_type,'message'=>$body,'booking_process_id'=>$booking_id]);
                                
                                // $contact->notify(new CourseReviewCustomer($data,$course_name,$booking_number));
                                $contact->notify((new CourseReviewCustomer($data,$course_name,$booking_number))->locale($contact->language_locale));  
                            }                  
                        /* }
                        catch (\Exception $e)
                        {
                            Log::error("An error occurs in send booking alert");   
                        } */
      
                }
        }
        Log::info("review_course:customers this cron is successfully executed.");
    }
}
