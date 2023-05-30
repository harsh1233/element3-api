<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\Notification;
use App\Models\Courses\Course;
use Illuminate\Console\Command;
use App\Jobs\SendPushNotification;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\User;
use App\Models\BookingProcess\BookingProcesses;
use App\Notifications\CourseReviewNotification;
use App\Models\BookingProcess\BookingProcessCourseDetails;
use App\Models\BookingProcess\BookingProcessCustomerDetails;
use App\Models\BookingProcess\BookingProcessInstructorDetails;

class PastCourseReview extends Command
{
    use Functions;  
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'past_course_review:customers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Email for Course review customers';

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

        $booking_processes_ids = BookingProcesses::where('is_trash', 0)
        ->orderBy('id', 'desc')
        ->pluck('id');
        $sender_id = null;

        foreach ($booking_processes_ids as $booking_id) {
            $course_details = BookingProcessCourseDetails::where('booking_process_id', $booking_id)
            ->first();
            $course = Course::find($course_details['course_id']);

            if($course){
                $title = "Past Course: ".$course->name." Review";
                $body = "Please Share Your Past Course: ".$course->name." Review ,we send email for share a review";
                $type = 19;

                $notification_data['course_id'] = $course_details['course_id'];
                $notification_data['booking_processes_id'] = $booking_id;
            }

            $current_date = date("Y-m-d H:i:s");

            if ($current_date > $course_details['EndDate_Time']) {
                $customer_details = BookingProcessCustomerDetails::where('booking_process_id', $booking_id)
                ->where('is_cancelled', false)
                ->where('is_new_invoice', false)
                ->select('customer_id', 'StartDate_Time', 'EndDate_Time')->get();

                $booking_processes = BookingProcesses::find($booking_id);
                $booking_number=$booking_processes->booking_number;

                $update_at = date('H:i:s');

                $email_scheduler_type = 'CR';
                
                foreach ($customer_details as $customer_detail) {
                    /* try
                    { */

                    $check_notification_send = Notification::where("receiver_id", $customer_detail->customer_id)->where('email_scheduler_type', $email_scheduler_type)
                                    ->where('booking_process_id', $booking_id)->count();

                    if ($check_notification_send) {
                        continue;
                    }

                    /* $instructor_detail = BookingProcessInstructorDetails::where('booking_process_id',$booking_id)
                    ->first();

                    if($instructor_detail){ */

                    $contact = Contact::where('id', $customer_detail->customer_id)->first();
                    // $customer = $contact->email;
                    $data['customer_name'] = $contact->first_name;

                    $data['deep_link'] =  url('/d/customer/course-review?booking_process_id='.$booking_id.'&course_id='.$course_details['course_id']);
                    /* $notification = Notification::create(["receiver_id"=>$customer_detail->customer_id,"type"=>$type,'email_scheduler_type'=>$email_scheduler_type,'message'=>$body,'booking_process_id'=>$booking_id]); */

                    // $contact->notify(new CourseReviewNotification($data,$booking_number));
                    $contact->notify((new CourseReviewNotification($data,$booking_number))->locale($contact->language_locale));
                    // }

                    $receiver_id = $customer_detail->customer_id;
                    $notification = Notification::create(['sender_id'=>$sender_id,"receiver_id"=>$receiver_id,"type"=>$type,'message'=>$body,'booking_process_id'=>$booking_id,'email_scheduler_type'=>$email_scheduler_type]);

                    /**For send push notification to customers */
                    if($course){
                        $user_token = User::where('contact_id', $customer_detail->customer_id)->select('id', 'is_notification', 'device_token', 'device_type', 'contact_id')->first();

                        if ($user_token) {
                            if ($user_token['is_notification'] == 1) {
                                if (!empty($user_token['device_token'])) {
                                    SendPushNotification::dispatch($user_token['device_token'], $user_token['device_type'], $title, $body, $type, $notification_data);
                                }
                            }
                        }
                    }
                }
                // $this->setPushNotificationData($customer_details, $type, $notification_data, $title, $body, $booking_id, $email_scheduler_type);

                /**End */

                /* }
                catch (\Exception $e)
                {
                    Log::error("An error occurs in send booking alert");
                } */
            }
        }
        Log::info("past_course_review:customers this cron is successfully executed.");
    }
}
