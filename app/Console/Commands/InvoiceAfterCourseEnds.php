<?php

namespace App\Console\Commands;
use PDF;
use Mail;
use App\User;
use App\Models\Contact;
use App\Models\Notification;
use App\Models\Courses\Course;
use Illuminate\Console\Command;
use App\Jobs\SendPushNotification;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\BookingProcess\BookingProcesses;
use App\Models\BookingProcess\BookingProcessCourseDetails;
use App\Models\BookingProcess\BookingProcessPaymentDetails;
use App\Models\BookingProcess\BookingProcessCustomerDetails;

class InvoiceAfterCourseEnds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'course_invoice_after_end:customers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Email for after course end send Invoice customer payment status is pending';

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
        $booking_datas = BookingProcessCustomerDetails::where('EndDate_Time','<',$current_date)
        ->where('is_cancelled', false)
        ->get();
        // $booking_ids_main = $booking_ids_main->whereIn('id',$booking_ids)->pluck('id');
        
        /* $booking_datas = BookingProcessPaymentDetails::whereIn('booking_process_id',$booking_ids_main)
        ->where('status','Pending')
        ->get()->toArray(); */
        // $i = 0;  
        // $sender_id = ;

        foreach ($booking_datas as $booking_customer_data) {
        
            $booking_payment_data = BookingProcessPaymentDetails::where('booking_process_id',$booking_customer_data['booking_process_id'])
            ->where('customer_id',$booking_customer_data['customer_id'])
            ->where('status','Pending')
            ->get()->toArray();
            
            foreach ($booking_payment_data as $booking_data) {
                $customer = Contact::find($booking_data['customer_id']);
                $payee = Contact::find($booking_data['payi_id']);
                 $i = 0;

                $booking_processes = BookingProcesses::find($booking_data['booking_process_id']);
                $booking_number = $booking_processes->booking_number;
                $booking_processes_course_datail = BookingProcessCourseDetails::where('booking_process_id', $booking_data['booking_process_id'])->first();
                $course = Course::find($booking_processes_course_datail->course_id);
                $course_name = ($course ? $course->name : null);
                $customer_email = $customer->email;

                /**If sub child exist */
                if(isset($booking_data['sub_child_id']) && $booking_data['sub_child_id']){
                    $sub_child = SubChildContact::find($booking_data['sub_child_id']);
                    $pdf_data['customer'][$i]['customer_name'] = $sub_child->first_name." ".$sub_child->last_name;
                }else{
                    $pdf_data['customer'][$i]['customer_name'] = $customer->salutation."".$customer->first_name." ".$customer->last_name;
                }

                $pdf_data['customer'][$i]['payi_name'] = $payee->salutation."".$payee->first_name." ".$payee->last_name;
                $pdf_data['customer'][$i]['payi_email'] = $payee->email;

                $template_data['customer_id'] = $booking_data['customer_id'];
                $template_data['customer_invoice'] = $booking_data['invoice_number'];
                $template_data['customer_name'] = $customer->first_name;
                $template_data['payment_status'] = $booking_data['payment_status'];

                $email_scheduler_type = 'PP';

                $check_notification_send = Notification::where("receiver_id", $booking_data['customer_id'])
                ->where('email_scheduler_type', $email_scheduler_type)
                ->where('booking_process_id', $booking_data['booking_process_id']);
                
                /**If sub child exist */
                if(isset($booking_data['sub_child_id']) && $booking_data['sub_child_id']){
                    $check_notification_send = $check_notification_send->where("sub_child_id", $booking_data['sub_child_id']);
                }
                
                $check_notification_send = $check_notification_send->count();

                if ($check_notification_send) {
                    continue;
                }

                if (config('constants.MAIL_HOST') == 'smtp.mailtrap.io') {
                    sleep(5);
                }

                $pdf = $booking_data['invoice_link'];
                // Log::info("invoice link : ".$pdf);
                
                try {
                    // check invoice is uplaoded in S3 or not other wise make new invoice and uplaod
                    file_get_contents($pdf);
                    
                } catch (\Exception $e) {
                   
                    (($payee)?$address_payee = ($payee->street_address1?$payee->street_address1:'').", ".($payee->city?$payee->city:'').", ".($payee->country_detail->name?$payee->country_detail->name:'')."":$address_payee="");

                    $pdf_data['booking_no']=$booking_processes->booking_number;

                    if(isset($booking_data['sub_child_id']) && $booking_data['sub_child_id']){
                        $sub_child = SubChildContact::find($booking_data['sub_child_id']);
                        $pdf_data['customer'][$i]['customer_name'] = $sub_child->first_name." ".$sub_child->last_name;
                    }else{
                        $pdf_data['customer'][$i]['customer_name'] = $customer->salutation."".$customer->first_name." ".$customer->last_name;
                    }
                    $pdf_data['customer'][$i]['payi_id'] = $payee->id;
                    $pdf_data['customer'][$i]['payi_name'] = $payee->salutation."".$payee->first_name." ".$payee->last_name;
                    $pdf_data['customer'][$i]['payi_address'] = $address_payee;
                    $pdf_data['customer'][$i]['payi_contact_no'] = $payee->mobile1;
                    $pdf_data['customer'][$i]['payi_email'] = $payee->email;
                    $pdf_data['customer'][$i]['no_of_days'] = $booking_data['no_of_days'];
                    $pdf_data['customer'][$i]['refund_payment'] = $booking_data['refund_payment'];
                    $pdf_data['customer'][$i]['total_price'] = $booking_data['total_price'];
                    $pdf_data['customer'][$i]['extra_participant'] = $booking_data['extra_participant'];
                    $pdf_data['customer'][$i]['discount'] = $booking_data['discount'];
                    $pdf_data['customer'][$i]['net_price'] = $booking_data['net_price'];
                    $pdf_data['customer'][$i]['vat_percentage'] = $booking_data['vat_percentage'];
                    $pdf_data['customer'][$i]['vat_amount'] = $booking_data['vat_amount'];
                    $pdf_data['customer'][$i]['vat_excluded_amount'] = $booking_data['vat_excluded_amount'];
                    $pdf_data['customer'][$i]['invoice_number'] = $booking_data['invoice_number'];
                    $pdf_data['customer'][$i]['invoice_date'] = $booking_data['created_at'];

                    $pdf_data['customer'][$i]['lunch_vat_amount'] = $booking_data['lunch_vat_amount'];
                    $pdf_data['customer'][$i]['lunch_vat_excluded_amount'] = $booking_data['lunch_vat_excluded_amount'];
                    $pdf_data['customer'][$i]['is_include_lunch'] = $booking_data['is_include_lunch'];
                    $pdf_data['customer'][$i]['include_lunch_price'] = $booking_data['include_lunch_price'];
                    $pdf_data['customer'][$i]['lunch_vat_percentage'] = $booking_data['lunch_vat_percentage'];
                    $pdf_data['customer'][$i]['payment_status'] = $booking_data['payment_status'];
                    
                    /**Add settelement amount details in invoice */
                    $pdf_data['customer'][$i]['settlement_amount'] = $booking_data['settlement_amount'] ? $booking_data['settlement_amount'] : null;
                    $pdf_data['customer'][$i]['settlement_description'] = $booking_data['settlement_description'];
                    /**End */
                    $pdf_data['customer'][$i]['outstanding_amount'] = $booking_data['outstanding_amount'];
                    $pdf_data['customer'][$i]['is_reverse_charge'] = $booking_data['is_reverse_charge'];

                    $pdf_data['customer'][$i]['course_name'] = $course_name;

                    $pdf = PDF::loadView('bookingProcess.customer_invoice', $pdf_data);

                    //upload invoice in s3
                    $url = 'Invoice/'.$payee->first_name.'_invoice'.mt_rand(1000000000, time()).'.pdf';
                    Storage::disk('s3')->put($url, $pdf->output());
                    $url = Storage::disk('s3')->url($url);
                    $booking_processes_payment_details = BookingProcessPaymentDetails::where('id', $booking_data['id']);
                    $update_data['invoice_link'] = $url;
                    $update = $booking_processes_payment_details->update($update_data);
                    //end s3 upload process
                    $pdf = $url;
                }
                
                /**Get default locale and set user language locale */
                $temp_locale = \App::getLocale();
                $user = User::where('email', $payee->email)->first();
                if($user){
                    \App::setLocale($user->language_locale);
                }
                /**End */
                
                Log::info("Booking number".$booking_number."invoice link : ".$pdf);
                Mail::send('email.customer.after_course_end', $template_data, function ($message) use ($pdf_data,$pdf,$course_name,$booking_number) {
                    //set for test email: $message->to("parthp@zignuts.com", $pdf_data['customer'][0]['payi_name'])
                    $message->to($pdf_data['customer'][0]['payi_email'], $pdf_data['customer'][0]['payi_name'])
                    ->subject(__('email_subject.invoice_course_end.description', ['booking_number' => $booking_number, 'course_name'=> $course_name]))
                    ->attachData(file_get_contents($pdf), "customer_invoice.pdf");
                });
                if (config('constants.MAIL_HOST') == 'smtp.mailtrap.io') {
                    sleep(5);
                }
                /**
                 * Date: 17-09-2020
                 * Description : Now not need to customer receive payment invoice
                 */
                // if($booking_data['customer_id']!=$booking_data['payi_id']){
                //     Mail::send('email.customer.after_course_end', $template_data, function ($message) use ($pdf_data,$pdf,$customer_email,$course_name,$booking_number) {
                //         //set for test email:  $message->to("parthp@zignuts.com", $pdf_data[0]['customer']['payi_name'])
                //         $message->to($customer_email, $pdf_data['customer'][0]['customer_name'])
                //         ->subject(__('email_subject.invoice_course_end.description', ['booking_number' => $booking_number, 'course_name'=> $course_name]))
                //         ->attachData(file_get_contents($pdf), "customer_invoice.pdf");
                //     });
                // }

                $title = "Your Course Payment is Pending";
                $body = "Your Course is pending the payment";
                $type = 12;
                
                $notification = Notification::create(["receiver_id"=>$booking_data['customer_id'],"type"=>$type,'email_scheduler_type'=>$email_scheduler_type,'message'=>$body,'booking_process_id'=>$booking_data['booking_process_id'],'sub_child_id' => $booking_data['sub_child_id']]);

                $user_token = User::where('contact_id', $customer->id)->select('id', 'is_notification', 'device_token', 'device_type')->first();

                if ($user_token) {

                    // foreach ($user_tokens as $key => $user_token) {
                    if ($user_token['is_notification'] == 1) {
                        if (!empty($user_token['device_token'])) {
                            
                            // $data['course_name'] = $course->name;
                            $data['booking_processes_id'] = $booking_data['booking_process_id'];

                            SendPushNotification::dispatch($user_token['device_token'], $user_token['device_type'], $title, $body, $type, $data);
                            /* $this->push_notification($user_token['device_token'], $user_token['device_type'], $title, $body, $type, $data); */
                        }
                    }
                }
                /**Set default language locale */
                \App::setLocale($temp_locale);
                
                // $i++;
            }
        }
        Log::info("course_invoice_after_end:customers this cron is successfully executed.");
    }
}
