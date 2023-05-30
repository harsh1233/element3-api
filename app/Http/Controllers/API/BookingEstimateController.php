<?php

namespace App\Http\Controllers\API;

use DB;
use PDF;
use Excel;
use App\User;
use DateTime;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Models\Courses\Course;
use App\Models\SequenceMaster;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Exports\BookingEstimateExport;
use Illuminate\Support\Facades\Storage;
use App\Models\BookingProcess\BookingEstimate;
use App\Models\BookingProcess\BookingProcesses;
use App\Models\BookingProcess\BookingProcessCourseDetails;
use App\Models\BookingProcess\BookingProcessPaymentDetails;
use App\Models\BookingProcess\BookingProcessCustomerDetails;

class BookingEstimateController extends Controller
{
    use Functions;

    /* API for create booking estimate */
    public function createBookingEstimate(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'nullable|integer|min:1',
            'customer_name' => 'required|max:50',
            'customer_mobile' => 'nullable|max:25',
            'customer_email' => 'nullable|email',
            'course_id' => 'integer|min:1',
            'course_detail_id' => 'nullable|integer|min:1',
            'start_date' => 'date',
            'end_date' => 'date',
            'start_time' => 'date_format:H:i:s',
            'end_time' => 'date_format:H:i:s',
            'total_price' => 'required|numeric',
            'discount' => 'nullable',
            'net_price' => 'required|nullable',
            'vat_percentage' => 'nullable', 
            'vat_amount' => 'nullable',
            'vat_excluded_amount' => 'nullable' ,
            'is_include_lunch' => 'nullable|in:1,0',
            'include_lunch_price' => 'nullable|numeric',
            'lunch_vat_amount' => 'nullable',
            'lunch_vat_excluded_amount' => 'nullable',
            'lunch_vat_percentage' => 'nullable',
            'settlement_amount' => 'nullable',
            'settlement_description' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        if(!$request->customer_id){
            /* $checkEmail = Contact::where('email',$estimate['customer_email'])->count();
            if ($checkEmail) return $this->sendResponse(false,__('strings.email_already_taken')); */
            $customer_name = explode(" ", $request->customer_name);
            for ($i=0; $i < count($customer_name); $i++) { 
                if($i==0){
                    $input_data['first_name'] = $customer_name[0];
                }elseif($i==1){
                    $input_data['middle_name'] = $customer_name[1];                     
                }elseif($i==2){
                    $input_data['last_name'] = $customer_name[2];    
                }
            }
            $input_data['email'] = $request->customer_email;
            $input_data['category_id'] = 1;
            $input_data['mobile1'] = $request->customer_mobile;
            $contact = Contact::create($input_data);
            $customer_id = $contact->id;
        }else{
            $customer_id = $request->customer_id;
        }
        
        $estimate_data = $request->all();
        $estimate_data['customer_id'] = $customer_id;
        $estimate_data['created_by'] = auth()->user()->id;
        $estimate_number = SequenceMaster::where('code', 'EN')->first();

        if ($estimate_number) {
            $estimate_data['estimate_number'] = "EN".date("m")."".date("Y").$estimate_number->sequence;
            $estimate_number->update(['sequence'=>$estimate_number->sequence+1]);
        }
        
        $create_estimate = BookingEstimate::create($estimate_data);
        // if($create_estimate)
        /**Add crm user action trail */
        if ($create_estimate) {
            $action_id = $create_estimate->id; //Booking Estimate id
            $action_type = 'A'; //A = Add
            $module_id = 17; //module id base module table
            $module_name = "Quote Offer"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        return $this->sendResponse(true, __('strings.booking_estimated_created_success'));
        
    }

    /* APi for list of booking process estimates */  
    public function bookingEstimateList(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'required|integer|min:1',
            'perPage' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $page = $request->page;
        $perPage = $request->perPage;
        // $userId = auth()->user()->id;
        
        $booking_estimate = BookingEstimate::query()->orderBy('id', 'desc');
      
        if($request->search) {
            $search = $request->search;
            $booking_estimate = $booking_estimate->where(function($query) use($search){
                $query->where('estimate_number','like',"%$search%");
                $query->orWhere('customer_name','like',"%$search%");
            });
        }

        $booking_estimate_count = $booking_estimate->count();

        $booking_processes = $booking_estimate->with(['customer_data'])
        ->with(['course_data'])
        ->with(['course_detail_data'])
        ->skip($perPage*($page-1))->take($perPage)
        ->get();

        $data = [
            'estimates' => $booking_processes,
            'count' => $booking_estimate_count
        ];

        if(!empty($_GET['is_export'])){
            return Excel::download(new BookingEstimateExport($booking_processes->toArray()), 'QuoteOffer.csv');  
        }

        return $this->sendResponse(true, 'success', $data);
    }

    /* Update booking process estimate */
    public function updateBookingEstimate(Request $request, $id)
    {   
        $v = validator($request->all(), [
            'customer_id' => 'nullable|integer|min:1',
            'customer_name' => 'required|max:50',
            'customer_mobile' => 'nullable|max:25',
            'customer_email' => 'nullable|email',
            'course_id' => 'integer|min:1',
            'course_detail_id' => 'nullable|integer|min:1',
            'start_date' => 'date',
            'end_date' => 'date',
            'start_time' => 'date_format:H:i:s',
            'end_time' => 'date_format:H:i:s',
            'total_price' => 'required|numeric',
            'discount' => 'nullable',
            'net_price' => 'required|nullable',
            'vat_percentage' => 'nullable', 
            'vat_amount' => 'nullable',
            'vat_excluded_amount' => 'nullable',
            'is_include_lunch' => 'nullable|in:1,0',
            'include_lunch_price' => 'nullable|numeric',
            'lunch_vat_amount' => 'nullable',
            'lunch_vat_excluded_amount' => 'nullable',
            'lunch_vat_percentage' => 'nullable', 
            'settlement_amount' => 'nullable',
            'settlement_description' => 'nullable',
        ]);
        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        
        $booking_estimate = BookingEstimate::find($id);
        
        if (!$booking_estimate) return $this->sendResponse(false,__('strings.quote_offer_not_found'));

        $update_data = $request->all();   
        $update_data['updated_by'] = auth()->user()->id;
        $update_estimate = $booking_estimate->update($update_data);
        // if($update_estimate)
        /**Add crm user action trail */
        if ($booking_estimate) {
            $action_id = $booking_estimate->id; //Booking Estimate id
            $action_type = 'U'; //U = Updated
            $module_id = 17; //module id base module table
            $module_name = "Quote Offer"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        return $this->sendResponse(true,__('strings.booking_estimated_updated_success'));
    
    }

    /* API for Deleting booking process estimate */
    public function deleteBookingEstimate($id)
    {
        $booking_estimate = BookingEstimate::find($id);
        if (!$booking_estimate) return $this->sendResponse(false,__('strings.quote_offer_not_found'));

        /**Add crm user action trail */
        if ($booking_estimate) {
            $action_id = $booking_estimate->id; //Booking Estimate id
            $action_type = 'D'; //D = Deleted
            $module_id = 17; //module id base module table
            $module_name = "Quote Offer"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        $delete_estimate = $booking_estimate->delete();
        if($delete_estimate)
        return $this->sendResponse(true,__('strings.booking_estimated_deleted_success'));
    }

    /* send Email with attachment of booking process estimate */
    public function sendBookingEstimateEmail($id)
    { 
        $booking_estimate = BookingEstimate::find($id);
        
        if (!$booking_estimate) return $this->sendResponse(false,__('strings.quote_offer_not_found'));

        $data = $booking_estimate;
        
        $course = DB::table('booking_estimates')->join('courses as c', 'c.id', '=', 'booking_estimates.course_id')
        ->where('booking_estimates.id', $id)
        ->first();

        $pdf = $data['invoice_link'];

        $pdf_data['estimate']['estimate_number'] = $data->estimate_number;
        $pdf_data['estimate']['course_name'] = ($course ? $course->name : null);
        $pdf_data['estimate']['customer_name'] = ($data->customer_name ?: null);
        $pdf_data['estimate']['start_date'] = $data->start_date;
        $pdf_data['estimate']['end_date'] = $data->end_date;
        $pdf_data['estimate']['start_time'] = $data->start_time;
        $pdf_data['estimate']['end_time'] = $data->end_time;
        $pdf_data['estimate']['customer_mobile'] = $data->customer_mobile;
        $pdf_data['estimate']['discount'] = $data->discount;
        $pdf_data['estimate']['total_price'] = $data->total_price;
        $pdf_data['estimate']['net_price'] = $data->net_price;
        $pdf_data['estimate']['vat_percentage'] = $data->vat_percentage;
        $pdf_data['estimate']['vat_amount'] = $data->vat_amount;
        $pdf_data['estimate']['vat_excluded_amount'] = $data->vat_excluded_amount;
        $pdf_data['estimate']['customer_email'] = $data['customer_email'];

        $pdf_data['estimate']['include_lunch_price'] = $data['include_lunch_price'];
        $pdf_data['estimate']['lunch_vat_percentage'] = $data['lunch_vat_percentage'];
        $pdf_data['estimate']['lunch_vat_amount'] = $data['lunch_vat_amount'];
        $pdf_data['estimate']['lunch_vat_excluded_amount'] = $data['lunch_vat_excluded_amount'];
        $pdf_data['estimate']['is_include_lunch'] = $data['is_include_lunch'];

        $pdf_data['estimate']['estimate_date'] = $data->created_at;
        $pdf_data['estimate']['settlement_amount'] = $data['settlement_amount'];
        
        /**Get default locale and set user language locale */
        $temp_locale = \App::getLocale();
        $user = User::where('email', $data['customer_email'])->first();
        if($user){
            \App::setLocale($user->language_locale);
        }
        /**End */

        if(!$pdf){
            $pdf = PDF::loadView('bookingProcess.estimate_invoice',$pdf_data);
            Mail::send('email.booking_estimate', [], function ($message) use ($pdf_data,$pdf) {
                $message->to($pdf_data['estimate']['customer_email'], $pdf_data['estimate']['customer_name'])
                // $message->to("parthp@zignuts.com", $pdf_data['estimate']['customer_name'])
                ->subject(__('email_subject.quote_offer_invoice'))
                ->attachData($pdf->output(), $pdf_data['estimate']['customer_name']."_QuoteOffer.pdf");
            });
            
            $url = 'EstimateInvoice/'.$data->customer_name.'_invoice'.mt_rand(1000000000, time()).'.pdf';
            Storage::disk('s3')->put($url,$pdf->output());
            $url = Storage::disk('s3')->url($url);

            $booking_estimate = BookingEstimate::where('id', $id);

            $update_data['invoice_link'] = $url;
            $update = $booking_estimate->update($update_data);
        }else{
            Mail::send('email.booking_estimate', [], function ($message) use ($pdf_data,$pdf) {
                $message->to($pdf_data['estimate']['customer_email'], $pdf_data['estimate']['customer_name'])
                // $message->to("parthp@zignuts.com", $pdf_data['estimate']['customer_name'])
                ->subject(__('email_subject.quote_offer_invoice'))
                ->attachData(file_get_contents($pdf), $pdf_data['estimate']['customer_name']."_QuoteOffer.pdf");
            });
        }
        /**Set default language locale */
        \App::setLocale($temp_locale);
        
        return $this->sendResponse(true, __('strings.send_estimate_invoice_sucess'));
        
    }

    /* dowenload Email with attachment of booking process estimate */
    public function downloadBookingEstimateInvoice($id)
    { 
        $booking_estimate = BookingEstimate::find($id);
        
        if (!$booking_estimate) return $this->sendResponse(false,__('strings.quote_offer_not_found'));

        $course = DB::table('booking_estimates')->join('courses as c', 'c.id', '=', 'booking_estimates.course_id')
        ->where('booking_estimates.id', $id)
        ->first();
        
        $data = $booking_estimate;
        
        $pdf_data['estimate']['estimate_number'] = $data->estimate_number;
        $pdf_data['estimate']['customer_name'] = $data->customer_name;
        $pdf_data['estimate']['course_name'] = ($course ? $course->name : null);
        $pdf_data['estimate']['start_date'] = $data->start_date;
        $pdf_data['estimate']['end_date'] = $data->end_date;
        $pdf_data['estimate']['start_time'] = $data->start_time;
        $pdf_data['estimate']['end_time'] = $data->end_time;
        $pdf_data['estimate']['customer_mobile'] = $data->customer_mobile;
        $pdf_data['estimate']['total_price'] = $data->total_price;
        $pdf_data['estimate']['discount'] = $data->discount;
        $pdf_data['estimate']['net_price'] = $data->net_price;
        $pdf_data['estimate']['vat_percentage'] = $data->vat_percentage;
        $pdf_data['estimate']['vat_amount'] = $data->vat_amount;
        $pdf_data['estimate']['vat_excluded_amount'] = $data->vat_excluded_amount;
        $pdf_data['estimate']['customer_email'] = $data['customer_email'];
        $pdf_data['estimate']['estimate_date'] = $data['created_at'];
        
        $pdf_data['estimate']['include_lunch_price'] = $data['include_lunch_price'];
        $pdf_data['estimate']['lunch_vat_percentage'] = $data['lunch_vat_percentage'];
        $pdf_data['estimate']['lunch_vat_amount'] = $data['lunch_vat_amount'];
        $pdf_data['estimate']['lunch_vat_excluded_amount'] = $data['lunch_vat_excluded_amount'];
        $pdf_data['estimate']['is_include_lunch'] = $data['is_include_lunch'];
        $pdf_data['estimate']['settlement_amount'] = $data['settlement_amount'];
        
        $pdf = PDF::loadView('bookingProcess.estimate_invoice',$pdf_data);
        return $pdf->download($data->customer_name.'_QuoteOffer.pdf');
    }

    /* Get booking process estimate details */
    public function getBookingEstimateDetails($id)
    {
         $booking_estimate = BookingEstimate::find($id);
         if (!$booking_estimate) return $this->sendResponse(false,__('strings.quote_offer_not_found'));
         $booking_estimate = BookingEstimate::with(['customer_data'])
         ->with(['course_data'])
         ->with(['course_detail_data'])
         ->where('id',$id)->first();
        //  ->get();
         return $this->sendResponse(true, 'success', $booking_estimate);
    }

    public function createMultipleBookingEstimate(Request $request)
    {
        $v = validator($request->all(), [
            'estimate_details.*.customer_id' => 'nullable|integer|min:1',
            'estimate_details.*.customer_name' => 'required|max:50',
            'estimate_details.*.customer_mobile' => 'nullable|max:25',
            'estimate_details.*.customer_email' => 'nullable|email',
            'estimate_details.*.course_id' => 'integer|min:1',
            'estimate_details.*.course_detail_id' => 'nullable|integer|min:1',
            'estimate_details.*.start_date' => 'date',
            'estimate_details.*.end_date' => 'date',
            'estimate_details.*.start_time' => 'date_format:H:i:s',
            'estimate_details.*.end_time' => 'date_format:H:i:s',
            'estimate_details.*.total_price' => 'required|numeric',
            'estimate_details.*.discount' => 'nullable',
            'estimate_details.*.net_price' => 'required|nullable',
            'estimate_details.*.vat_percentage' => 'nullable', 
            'estimate_details.*.vat_amount' => 'nullable',
            'estimate_details.*.vat_excluded_amount' => 'nullable' ,
            'estimate_details.*.is_include_lunch' => 'nullable|in:1,0',
            'estimate_details.*.include_lunch_price' => 'nullable|numeric',
            'estimate_details.*.lunch_vat_amount' => 'nullable',
            'estimate_details.*.lunch_vat_excluded_amount' => 'nullable',
            'estimate_details.*.lunch_vat_percentage' => 'nullable',
            'estimate_details.*.settlement_amount' => 'nullable',
            'estimate_details.*.settlement_description' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        foreach($request->estimate_details as $estimate){
            if(!isset($estimate['customer_id'])){
                /* $checkEmail = Contact::where('email',$estimate['customer_email'])->count();
                if ($checkEmail) return $this->sendResponse(false,__('strings.email_already_taken')); */
                $customer_name = explode(" ", $estimate['customer_name']);
                for ($i=0; $i < count($customer_name); $i++) {
                    if($i==0){
                        $input_data['first_name'] = $customer_name[0];
                    }elseif($i==1){
                        $input_data['middle_name'] = $customer_name[1];                     
                    }elseif($i==2){
                        $input_data['last_name'] = $customer_name[2];    
                    }
                }
                $input_data['email'] = $estimate['customer_email'];
                $input_data['category_id'] = 1;
                $input_data['mobile1'] = $estimate['customer_mobile'];
                $contact = Contact::create($input_data);
                $customer_id = $contact->id;
            }else{
                $customer_id = $estimate['customer_id'];
            }
            
            $estimate_data = $estimate;
            $estimate_data['customer_id'] = $customer_id;
            $estimate_data['created_by'] = auth()->user()->id;
            $estimate_number = SequenceMaster::where('code', 'EN')->first();

            if ($estimate_number) {
                $estimate_data['estimate_number'] = "EN".date("m")."".date("Y").$estimate_number->sequence;
                $estimate_number->update(['sequence'=>$estimate_number->sequence+1]);
            }
            
            $create_estimate = BookingEstimate::create($estimate_data);
            // if($create_estimate)
            /**Add crm user action trail */
            if ($create_estimate) {
                $action_id = $create_estimate->id; //Booking Estimate id
                $action_type = 'A'; //A = Add
                $module_id = 17; //module id base module table
                $module_name = "Quote Offer"; //module name base module table
                $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
            }
            /**End manage trail */
        }

        return $this->sendResponse(true, __('strings.booking_estimated_created_success'));
        
    }

    /*Send Email with attachment of booking process estimate */
    public function sendMultipleBookingEstimateEmail(Request $request)
    {
        $v = validator($request->all(), [
            'ids' => 'required|array|exists:booking_estimates,id',
            'email' => 'required|email',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $email = $request->email;

        $pdf = $this->getMultipleEstimateInvoiceUrl($request->ids);

        /**Get default locale and set user language locale */
        $temp_locale = \App::getLocale();
        $user = User::where('email', $email)->first();
        if($user){
            \App::setLocale($user->language_locale);
        }
        /**End */

        Mail::send('email.booking_estimate',[], function ($message) use ($pdf, $email) {
            $message->to($email)
            ->subject(__('email_subject.quote_offer_invoice'))
            ->attachData($pdf->output(), "QuoteOffer.pdf");
        });
        
        /**Set default language locale */
        \App::setLocale($temp_locale);

        return $this->sendResponse(true, __('strings.send_estimate_invoice_sucess'));
    }

    /**Get invoice pdf */
    public function getMultipleEstimateInvoiceUrl($ids){
        $i = 0;
        $total_estimate_amount = 0;
        foreach($ids as $id){
            $booking_estimate = BookingEstimate::find($id);
            
            $course = DB::table('booking_estimates')->join('courses as c', 'c.id', '=', 'booking_estimates.course_id')
            ->where('booking_estimates.id', $id)
            ->first();

            $data = $booking_estimate;
            $total_estimate_amount = $total_estimate_amount + $data->net_price;
            $pdf_data['estimate'][$i]['estimate_number'] = $data->estimate_number;
            $pdf_data['estimate'][$i]['course_name'] = ($course ? $course->name : null);
            $pdf_data['estimate'][$i]['customer_name'] = $data->customer_name;
            $pdf_data['estimate'][$i]['start_date'] = $data->start_date;
            $pdf_data['estimate'][$i]['end_date'] = $data->end_date;
            $pdf_data['estimate'][$i]['start_time'] = $data->start_time;
            $pdf_data['estimate'][$i]['end_time'] = $data->end_time;
            $pdf_data['estimate'][$i]['customer_mobile'] = $data->customer_mobile;
            $pdf_data['estimate'][$i]['discount'] = $data->discount;
            $pdf_data['estimate'][$i]['total_price'] = $data->total_price;
            $pdf_data['estimate'][$i]['net_price'] = $data->net_price;
            $pdf_data['estimate'][$i]['vat_percentage'] = $data->vat_percentage;
            $pdf_data['estimate'][$i]['vat_amount'] = $data->vat_amount;
            $pdf_data['estimate'][$i]['vat_excluded_amount'] = $data->vat_excluded_amount;
            $pdf_data['estimate'][$i]['customer_email'] = $data['customer_email'];
    
            $pdf_data['estimate'][$i]['include_lunch_price'] = $data['include_lunch_price'];
            $pdf_data['estimate'][$i]['lunch_vat_percentage'] = $data['lunch_vat_percentage'];
            $pdf_data['estimate'][$i]['lunch_vat_amount'] = $data['lunch_vat_amount'];
            $pdf_data['estimate'][$i]['lunch_vat_excluded_amount'] = $data['lunch_vat_excluded_amount'];
            $pdf_data['estimate'][$i]['is_include_lunch'] = $data['is_include_lunch'];
    
            $pdf_data['estimate'][$i]['estimate_date'] = $data->created_at;
            $pdf_data['estimate'][$i]['settlement_amount'] = $data['settlement_amount'];

            $i = $i + 1;
        }
        $pdf_data['total_estimate_amount'] = $total_estimate_amount;
        $pdf = PDF::loadView('bookingProcess.multiple_estimate_invoice',$pdf_data);
        return $pdf;
    }

    /*Dowenload Email with attachment of booking process estimate */
    public function downloadMultipleBookingEstimateInvoice()
    { 
        if(isset($_GET['ids'])){
            $ids = explode(',',$_GET['ids']);
            $pdf = $this->getMultipleEstimateInvoiceUrl($ids);
            return $pdf->download('Multiple_Estimate_Quote_Offer.pdf');
        }else{
            echo __('strings.required_validation',['name' => 'Quote offer ids' ]);
        }
    }

    /**Convert multiple estimate to booking */
    public function multipleEstimateToBooking(Request $request){
        $v = validator($request->all(), [
            'ids' => 'required|array|exists:booking_estimates,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        foreach($request->ids as $id){
            $booking_data = array();
            $booking_course_data = array();
            $booking_customer_data = array();
            $booking_payment_data = array();

            $booking_estimate = BookingEstimate::find($id);
            $booking_data['created_by'] = auth()->user()->id;
            $booking_number = SequenceMaster::where('code', 'BN')->first();

            if ($booking_number) {
                $booking_data['booking_number'] = $booking_number->sequence;
                $booking_data['is_draft'] = 1;
                $update_data['sequence'] = $booking_data['booking_number']+1;
                $booking_data['booking_number'] = "EL".date("m")."".date("Y").$booking_number->sequence;
                $booking_number->update(['sequence'=>$booking_number->sequence+1]);
            }
            $booking_data['QR_number'] = mt_rand(100000000, 999999999);
            $booking_processes = BookingProcesses::create($booking_data);

            $course = Course::find($booking_estimate->course_id);
            
            $booking_course_data['course_type'] = $course->type;
            $booking_course_data['course_id'] = $course->id;
            $booking_course_data['course_detail_id'] = $booking_estimate->course_detail_id;
            $booking_course_data['StartDate_Time'] = $booking_estimate->start_date.' '.$booking_estimate->start_time;
            $booking_course_data['EndDate_Time'] = $booking_estimate->end_date.' '.$booking_estimate->end_time;
            $booking_course_data['start_date'] = $booking_estimate->start_date;
            $booking_course_data['end_date'] = $booking_estimate->end_date;
            $booking_course_data['start_time'] = $booking_estimate->start_time;
            $booking_course_data['end_time'] = $booking_estimate->end_time;
            
            $booking_course_data['created_by'] = auth()->user()->id;
            $booking_course_data['booking_process_id'] = $booking_processes->id;
            BookingProcessCourseDetails::create($booking_course_data);

            $datetime1 = new DateTime($booking_estimate->start_date);
            $datetime2 = new DateTime($booking_estimate->end_date);
            
            $interval = $datetime1->diff($datetime2);
            $days = $interval->format('%a') + 1;
            $hours = $interval->format('%h');

            $booking_customer_data['customer_id'] = $booking_estimate->customer_id;
            $booking_customer_data['payi_id'] = $booking_estimate->customer_id;
            $booking_customer_data['course_detail_id'] = $booking_estimate->course_detail_id;
            $booking_customer_data['StartDate_Time'] = $booking_estimate->start_date.' '.$booking_estimate->start_time;
            $booking_customer_data['EndDate_Time'] = $booking_estimate->end_date.' '.$booking_estimate->end_time;
            $booking_customer_data['start_date'] = $booking_estimate->start_date;
            $booking_customer_data['end_date'] = $booking_estimate->end_date;
            $booking_customer_data['start_time'] = $booking_estimate->start_time;
            $booking_customer_data['end_time'] = $booking_estimate->end_time;
            $booking_customer_data['no_of_days'] = $days;
            $booking_customer_data['hours_per_day'] = $hours;
            $booking_customer_data['is_payi'] = 'Yes';
            $booking_customer_data['QR_number'] = $booking_processes->id.$booking_estimate->customer_id.mt_rand(100000, 999999);
            $booking_customer_data['cal_payment_type'] = $course->cal_payment_type;
            $booking_customer_data['created_by'] = auth()->user()->id;
            $booking_customer_data['booking_process_id'] = $booking_processes->id;
            BookingProcessCustomerDetails::create($booking_customer_data);

            $invoice_number = SequenceMaster::where('code', 'INV')->first();

            if ($invoice_number) {
                $booking_payment_data['invoice_number'] = $invoice_number->sequence;
                $booking_payment_data['invoice_number'] = "INV".date("m")."".date("Y").$booking_payment_data['invoice_number'];
                $invoice_number->update(['sequence'=>$invoice_number->sequence+1]);
            }

            $booking_payment_data['customer_id'] = $booking_estimate->customer_id;
            $booking_payment_data['payi_id'] = $booking_estimate->customer_id;
            $booking_payment_data['course_detail_id'] = $booking_estimate->course_detail_id;
            $booking_payment_data['status'] = 'Pending';
            $booking_payment_data['total_price'] = $booking_estimate->total_price;
            $booking_payment_data['discount'] = $booking_estimate->discount;
            $booking_payment_data['net_price'] = $booking_estimate->net_price;
            $booking_payment_data['no_of_days'] = $days;
            $booking_payment_data['vat_percentage'] = $booking_estimate->vat_percentage;
            $booking_payment_data['vat_amount'] = $booking_estimate->vat_amount;
            $booking_payment_data['vat_excluded_amount'] = $booking_estimate->vat_excluded_amount;
            $booking_payment_data['cal_payment_type'] = $course->cal_payment_type;
            $booking_payment_data['created_by'] = auth()->user()->id;
            $booking_payment_data['booking_process_id'] = $booking_processes->id;
            $booking_payment_data['settlement_amount'] = $booking_estimate->settlement_amount;
            $booking_payment_data['settlement_description'] = $booking_estimate->settlement_description;
            $booking_payment_data['outstanding_amount'] = $booking_estimate->net_price;

            BookingProcessPaymentDetails::create($booking_payment_data);
        }
        return $this->sendResponse(true, __('strings.quote_offer_booking_success'));
    }
}
