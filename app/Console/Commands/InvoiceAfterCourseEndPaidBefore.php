<?php

namespace App\Console\Commands;

use PDF;
use Mail;
use App\User;
use App\Models\Contact;
use App\Models\Notification;
use App\Models\ContactAddress;
use App\Models\Courses\Course;
use Illuminate\Console\Command;
use App\Jobs\SendPushNotification;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use App\Models\SubChild\SubChildContact;
use App\Models\BookingProcess\BookingProcesses;
use App\Models\BookingProcess\BookingProcessCourseDetails;
use App\Models\BookingProcess\BookingProcessPaymentDetails;
use App\Models\BookingProcess\BookingProcessCustomerDetails;

class InvoiceAfterCourseEndPaidBefore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'course_invoice_after_end_paid_before:customers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Email for after course end send Invoice customer payment status is success';

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
        
        // $booking_datas = BookingProcessPaymentDetails::whereIn('booking_process_id',$booking_ids_main)
        // ->where('status','Success')
        // /* ->select('customer_id','payi_id') */->get()->toArray();

        $i = 0;  
        // $sender_id = ;
        
        foreach ($booking_datas as $booking_customer_data) {
            $booking_payment_data = BookingProcessPaymentDetails::where('booking_process_id', $booking_customer_data['booking_process_id'])
            ->where('customer_id', $booking_customer_data['customer_id'])
            ->where('status', 'Success')
            ->get()->toArray();

            foreach ($booking_payment_data as $booking_data) {
                $customer = Contact::find($booking_data['customer_id']);
                $payee = Contact::find($booking_data['payi_id']);

                $contact_address_customer = ContactAddress::with('country_detail')->where('contact_id', $booking_data['customer_id'])->first();

                ($contact_address_customer)?$address_customer = $contact_address_customer->street_address1.", ".$contact_address_customer->city.", ".$contact_address_customer->country_detail->name.".":$address_customer="";

                $contact_address_payee = ContactAddress::with('country_detail')->where('contact_id', $booking_data['payi_id'])->first();

                ($contact_address_payee)?$address_payee = $contact_address_payee->street_address1.", ".$contact_address_payee->city.", ".$contact_address_payee->country_detail->name.".":$address_payee="";

                // dd($payee->first_name);
            
                $booking_processes = BookingProcesses::find($booking_data['booking_process_id']);

                $booking_course_details = BookingProcessCourseDetails::where('booking_process_id', $booking_data['booking_process_id'])
                ->get()->first();
                if ($booking_course_details) {
                    $course_name = Course::where('id', $booking_course_details->course_id)->first();
                }

                $pdf_data['booking_no']=$booking_processes->booking_number;
                $booking_number=$booking_processes->booking_number;

                // $contact_data = Contact::with('address.country_detail:id,name,code')->find($request->customer_id);
                /**If sub child exist */
                if(isset($booking_data['sub_child_id']) && $booking_data['sub_child_id']){
                    $sub_child = SubChildContact::find($booking_data['sub_child_id']);
                    $pdf_data['customer'][$i]['customer_name'] = $sub_child->first_name." ".$sub_child->last_name;
                }else{
                    $pdf_data['customer'][$i]['customer_name'] = $customer->salutation."".$customer->first_name." ".$customer->last_name;
                }
                
                $customer_email = $customer->email;
                // $contact = Contact::with('address.country_detail:id,name,code')->find($payment_detail->payi_id);
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

                // $pdf = PDF::loadView('bookingProcess.customer_invoice', $pdf_data);

                $template_data['customer_id'] = $booking_data['customer_id'];
                $template_data['customer_invoice'] = $booking_data['invoice_number'];
                $template_data['customer_name'] = $customer->first_name;

                $email_scheduler_type = 'PS';
            
                if (!empty($course_name)) {
                    $course_name = $course_name->name;
                } else {
                    $course_name = '';
                }
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
                
                /**Get default locale and set user language locale */
                $temp_locale = \App::getLocale();
                $user = User::where('email', $payee->email)->first();
                if($user){
                    \App::setLocale($user->language_locale);
                }
                /**End */
                
                Mail::send('email.customer.after_course_end_payment_success', $template_data, function ($message) use ($pdf_data,$course_name,$booking_number) {
                    $message->to($pdf_data['customer'][0]['payi_email'], $pdf_data['customer'][0]['payi_name'])
                // $message->to("parthp@zignuts.com", $pdf_data['customer'][0]['payi_name'])
                ->subject(__('email_subject.invoice_course_end_paid.description', ['booking_number' => $booking_number, 'course_name'=> $course_name]));
                    // ->attachData($pdf->output(), "customer_invoice.pdf");
                });
                if (config('constants.MAIL_HOST') == 'smtp.mailtrap.io') {
                    sleep(5);
                }
                if ($booking_data['customer_id']!=$booking_data['payi_id']) {
                    Mail::send('email.customer.after_course_end_payment_success', $template_data, function ($message) use ($pdf_data,$customer_email,$course_name,$booking_number) {
                        $message->to($customer_email, $pdf_data['customer'][0]['customer_name'])
                    // $message->to("parthp@zignuts.com", $pdf_data['customer'][0]['customer_name'])
                    ->subject(__('email_subject.invoice_course_end_paid.description', ['booking_number' => $booking_number, 'course_name'=> $course_name]));
                        // ->attachData($pdf->output(), "customer_invoice.pdf");
                    });
                }

                $title = "Your Course Payment is Success";
                $body = "Your Course is Success the payment";
                $type = 13;
                
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
                
                $i++;
            }
        }
        Log::info("course_invoice_after_end_paid_before:customers this command is successfully execute".json_encode($fields));
                // Log::info("Result ". json_encode($result));
    }
}
