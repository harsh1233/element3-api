<?php

namespace App\Jobs;

use PDF;
use App\User;
use App\Models\Contact;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use App\Models\ContactAddress;
use App\Jobs\SendPushNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\BookingProcess\BookingProcesses;
use App\Models\BookingProcess\BookingProcessPaymentDetails;
use App\Models\BookingProcess\BookingProcessCustomerDetails;

class TwoWeekInvoicePendingReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $invoice_ids;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($ids)
    {
        $this->invoice_ids = $ids;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Two week invoice pending reminder job execution started!.");
        DB::beginTransaction();
            foreach($this->invoice_ids as $id){
                $payment_details = BookingProcessPaymentDetails::find($id);
                $booking_processes = BookingProcesses::find($payment_details->booking_process_id);
                $pdf_data['booking_no']=$booking_processes->booking_number;
                
                $customer = Contact::find($payment_details->payi_id);
                $booking_number = $booking_processes->booking_number;
                $invoice_number = $payment_details->invoice_number;
                $payment_status = $payment_details->status;
        
                $template_data = [
                    'customer_name' => $customer_name = ucfirst($customer->salutation)." ".ucfirst($customer->first_name)." ".ucfirst($customer->last_name),
                    'booking_number' => $booking_number,
                    'invoice_number' => $invoice_number,
                    'payment_status' => $payment_status
                ];
        
                $course = DB::table('booking_process_course_details')->join('courses as c', 'c.id', '=', 'booking_process_course_details.course_id')
                ->where('booking_process_course_details.booking_process_id', $payment_details->booking_process_id)
                ->select('c.id', 'c.name')->first();

                $contact_data = Contact::with('address.country_detail:id,name,code')->find($payment_details->customer_id);

                /**If sub child exist */
                if(isset($payment_details->sub_child_id) && $payment_details->sub_child_id){
                    $contact_data = SubChildContact::find($payment_details->sub_child_id);
                    $pdf_data['customer'][0]['customer_name'] = $contact_data->first_name." ".$contact_data->last_name;
                }else{
                    $pdf_data['customer'][0]['customer_name'] = $contact_data->salutation."".$contact_data->first_name." ".$contact_data->last_name;
                }

                $customer_email = $contact_data->email;
                $contact = Contact::with('address.country_detail:id,name,code')->find($payment_details->payi_id);

                $contact_address = ContactAddress::with('country_detail')->where('contact_id', $payment_details->payi_id)->first();

                ($contact_address)?$address = $contact_address->street_address1.", ".$contact_address->city.", ".$contact_address->country_detail->name.".":$address="";

                $pdf_data['customer'][0]['payi_id'] = $payment_details->payi_id;
                $pdf_data['customer'][0]['payi_name'] = $contact['salutation']."".$contact['first_name']." ".$contact['last_name'];
                $pdf_data['customer'][0]['payi_address'] = $address;
                $pdf_data['customer'][0]['payi_contact_no'] = $contact['mobile1'];
                $pdf_data['customer'][0]['payi_email'] = $contact['email'];
                $pdf_data['customer'][0]['no_of_days'] = $payment_details->no_of_days;
                $pdf_data['customer'][0]['refund_payment'] = $payment_details->refund_payment;
                $pdf_data['customer'][0]['total_price'] = $payment_details->total_price;
                $pdf_data['customer'][0]['extra_participant'] = $payment_details->extra_participant;
                $pdf_data['customer'][0]['discount'] = $payment_details->discount;
                $pdf_data['customer'][0]['net_price'] = $payment_details->net_price;
                $pdf_data['customer'][0]['vat_percentage'] = $payment_details->vat_percentage;
                $pdf_data['customer'][0]['vat_amount'] = $payment_details->vat_amount;
                $pdf_data['customer'][0]['vat_excluded_amount'] = $payment_details->vat_excluded_amount;
                $pdf_data['customer'][0]['invoice_number'] = $payment_details->invoice_number;
                $pdf_data['customer'][0]['invoice_date'] = $payment_details->created_at;
                $pdf_data['customer'][0]['lunch_vat_amount'] = $payment_details->lunch_vat_amount;
                $pdf_data['customer'][0]['lunch_vat_excluded_amount'] = $payment_details->lunch_vat_excluded_amount;
                $pdf_data['customer'][0]['is_include_lunch'] = $payment_details->is_include_lunch;
                $pdf_data['customer'][0]['include_lunch_price'] = $payment_details->include_lunch_price;
                $pdf_data['customer'][0]['lunch_vat_percentage'] = $payment_details->lunch_vat_percentage;
                $pdf_data['customer'][0]['payment_status'] = $payment_details->status;
                $pdf_data['customer'][0]['settlement_amount'] = $payment_details->settlement_amount;
                $pdf_data['customer'][0]['settlement_description'] = $payment_details->settlement_description;
                $pdf_data['customer'][0]['outstanding_amount'] = $payment_details->outstanding_amount;
                $pdf_data['customer'][0]['is_reverse_charge'] = $payment_details->is_reverse_charge;
                $pdf_data['customer'][0]['course_name'] = ($course ? $course->name : null);

                if($payment_details->invoice_link){
                    $pdf_output = file_get_contents($payment_details->invoice_link);
                }else{
                    $pdf = PDF::loadView('bookingProcess.customer_invoice', $pdf_data);
                    $pdf_output = $pdf->output();
                }
        
                $payment_details->increment('no_invoice_sent');
                $template_data['payment_link'] = $payment_details->payment_link;

                /**Get default locale and set user language locale */
                $temp_locale = \App::getLocale();
                $user = User::where('email', $contact['email'])->first();
                if($user){
                    \App::setLocale($user->language_locale);
                }
                /**End */
        
                /**Send reminder email with attachment */
                Mail::send('email.customer.send_again_customer_invoice', $template_data, function ($message) use ($pdf_data,$pdf_output,$booking_number,$invoice_number) {
                    $message->to($pdf_data['customer'][0]['payi_email'], $pdf_data['customer'][0]['payi_name'])
                    ->subject(__('email_subject.two_week_invoice_reminder', ['booking_number' => $booking_number, 'invoice_number' => $invoice_number ]))
                    ->attachData($pdf_output, $invoice_number."_Invoice.pdf");
                });
        
                /**Send reminder notification */
                $user_detail = User::where('contact_id',$payment_details->payi_id)
                ->where('is_notification',1)
                ->select('id','email','device_token','device_type')
                ->first();
                if($user_detail){
                    $type = 34;
                    $notifaction_data['course_id'] = ($course ? $course->id : null); 
                    $notifaction_data['booking_process_id'] = $payment_details->booking_process_id; 
                    $title = "Pending invoice reminder for course : ".($course ? $course->name : null);
                    $body = "Hello ".$contact['first_name']." your invoice is pending for course ".$course->name;
                    SendPushNotification::dispatch($user_detail['device_token'],$user_detail['device_type'],$title,$body,$type,$notifaction_data);

                    Notification::create(['sender_id'=>null,"receiver_id"=>$payment_details->payi_id,"type"=>$type,'message'=>$body,'booking_process_id'=>$payment_details->booking_process_id]);
                }

                /**Set default language locale */
                \App::setLocale($temp_locale);
            }
        DB::commit();
        Log::info("Two week invoice pending reminder job successfully executed!.");
    }
}
