<?php

namespace App\Http\Controllers\API;

use PDF;
use Mail;
use Excel;
use App\User;
use App\Models\Contact;
use App\Models\Payroll;
use App\Models\Payslip;
use Illuminate\Http\Request;
use App\Jobs\GeneratePayroll;
use App\Jobs\GeneratePayslip;
use App\Exports\PayrollExport;
use App\Exports\PayrollListExport;
use App\Jobs\SendPushNotification;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Notification;
use App\Models\InstructorActivity\InstructorActivityTimesheet;

class PayrollController extends Controller
{
    use Functions;

    /* List of Payrolls */
    public function list(Request $request)
    {
        /* If Export to csv request ignore validation */
        if (!$request->is_export) {
            $v = validator($request->all(), [
                'page' => 'required|integer|min:1',
                'perPage' => 'required|integer|min:1',
            ]);

            if ($v->fails()) {
                return $this->sendResponse(false, $v->errors()->first());
            }
        }

        $page = $request->page;
        $perPage = $request->perPage;
        $payrolls = Payroll::query();
        $payrolls = Payroll::orderBy('year', 'desc')->orderBy('month', 'desc');
        $payrollCount = $payrolls->count();
        /* If Export to csv request ignore pagination */
        if (!$request->is_export) {
        $payrolls->skip($perPage*($page-1))->take($perPage);
        }
        $payrolls = $payrolls->get();


        /*** For Export Data To CSV  ***/
        if ($request->is_export) {
            return Excel::download(new PayrollListExport($payrolls->toArray()), 'Payroll-List.csv');
        }
        /* End Export to CSV */


        $data = [
            'payrolls' => $payrolls,
            'count' => $payrollCount
        ];
        return $this->sendResponse(true, 'list of payrolls', $data);
    }

    /* Create payroll */
    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'year'         => 'required|integer|min:1970|max:2100',
            'month'        => 'required|date_format:m',
            'working_days' => 'required|integer|min:1|max:31'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $created_by = auth()->user()->id;
        $total_days = date('t', strtotime($request->get('year').'-'.$request->get('month')));

        GeneratePayroll::dispatch($request->only('year', 'month', 'working_days')+['total_days'=>$total_days],$created_by);

        return $this->sendResponse(true, 'Payroll generation started.');
    }

    /* Refresh payroll */
    public function refresh($id)
    {
        $payroll = Payroll::find($id);
        if (!$payroll) {
            return $this->sendResponse(false, 'Payroll not found');
        }
        
        $created_by = auth()->user()->id;
        GeneratePayroll::dispatch($payroll->only('year', 'month', 'working_days', 'total_days'),$created_by);

        return $this->sendResponse(true, __('strings.payroll_refresh_started'));
    }

    /* Delete payroll */
    public function delete($id)
    {
        $payroll = Payroll::find($id);
        if (!$payroll) {
            return $this->sendResponse(false, 'Payroll not found');
        }
        Payslip::where('payroll_id', $payroll->id)->delete();

        /**Add crm user action trail */
        if($payroll){
            $action_id = $payroll->id; //payroll id
            $action_type = 'D'; //D = Deleted
            $module_id = 25; //module id base module table
            $module_name = "Payroll"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        $payroll->delete();

        return $this->sendResponse(true, __('strings.payroll_deleted_success'));
    }

    /* Get Single Payroll with Payslip */
    public function get($id)
    {
        $payroll = Payroll::with('payslips.contact.category_detail')->find($id);
        $year_month = $payroll->year.($payroll->month < 10 ? "0$payroll->month" : $payroll->month);

        $payrolls = Payroll::all();
        $payrolls->transform(function ($payroll_item) {
            $payroll_item->year_month = $payroll_item->year.($payroll_item->month < 10 ? "0$payroll_item->month" : $payroll_item->month);
            return $payroll_item;
        });

        $last_payroll = $payrolls->sortByDesc('year_month')->where('year_month', '<', $year_month)->first();
        $previous = json_decode("{}");
        if ($last_payroll) {
            $month = $last_payroll->month < 10 ? "0$last_payroll->month" : $last_payroll->month;
            $month_name = date('F', strtotime('1995'.$month.'05'));

            $previous = [
                'text' => $month_name.' '.$last_payroll->year,
                'id' => $last_payroll->id
            ];
        }

        $next_payroll = $payrolls->sortByDesc('year_month')->where('year_month', '>', $year_month)->last();
        $next = json_decode("{}");
        if ($next_payroll) {
            $month = $next_payroll->month < 10 ? "0$next_payroll->month" : $next_payroll->month;
            $month_name = date('F', strtotime('1995'.$month.'05'));
            $next = [
                'text' => $month_name.' '.$next_payroll->year,
                'id' => $next_payroll->id
            ];
        }

        if (!$payroll) {
            return $this->sendResponse(false, 'Payroll not found');
        }

        return $this->sendResponse(true, 'Payroll with payslips', [
            'payroll' => $payroll,
            'pagination' => [
                'previous' => $previous,
                'next' => $next,
            ]
        ]);
    }

    /* New API for Get Single Payroll with Payslip with search */
    public function view(Request $request,$id)
    {   
        if(!$request->is_export){
            $v = validator($request->all(), [
                'page' => 'required|integer|min:1',
                'perPage' => 'required|integer|min:1',
            ]);
    
            if ($v->fails()) {
                return $this->sendResponse(false, $v->errors()->first());
            }
        }

        $payroll = Payroll::find($id);

        if (!$payroll) {
            return $this->sendResponse(false, 'Payroll not found');
        }
        
        $payslips = Payslip::query();
        $payslips = $payslips->where('payroll_id', $id);

        if($request->status && $request->status!='null'){
            $payslips = $payslips->where('status', $request->status);
        }
        if($request->salary_type && $request->salary_type!='null'){
            $payslips = $payslips->where('salary_type', $request->salary_type);            
        }
        $payslips_count = $payslips->count();

        /**If is_export=1 passed then can not set pagination wise data */
        if (!$request->is_export) {
            $page = $request->page;
            $perPage = $request->perPage;
            $payslips = $payslips->skip($perPage*($page-1))->take($perPage);
        }

        $payslips = $payslips->with('contact.category_detail')->get();
        
        /**For manage instructor timesheet status */
        $time = strtotime("$payroll->year-$payroll->month-01");
        $first_day = date("Y-m-d", $time);
        $last_day = date("Y-m-t", $time);
       
        foreach ($payslips as $payslip) {
            if ($payslip->contact_id) {
                $contact = Contact::find($payslip->contact_id);
                if ($contact) {
                    // if ($contact->user_detail->id) {
                    //     $completed_activites = InstructorActivityTimesheet::where('instructor_id', $contact->user_detail->id)->where('activity_date', '>=', $first_day)
                    //     ->where('activity_date', '<=', $last_day)
                    //     ->where('status', 'IP');
                    //     $pending_activites = InstructorActivityTimesheet::where('instructor_id', $contact->user_detail->id)->where('activity_date', '>=', $first_day)
                    //     ->where('activity_date', '<=', $last_day)
                    //     ->where('status', 'P');
                    //     $total_activites = InstructorActivityTimesheet::where('instructor_id', $contact->user_detail->id)->where('activity_date', '>=', $first_day)
                    //     ->where('activity_date', '<=', $last_day);
            
                    //     if ($completed_activites->count()==$total_activites->count()) {
                    //         $payslip->instructor_status = "Completed";
                    //         if (($completed_activites->count()==0 && $total_activites->count()==0)) {
                    //             $payslip->instructor_status = null;
                    //         }
                    //     } elseif ($pending_activites->count()==$total_activites->count()) {
                    //         $payslip->instructor_status = "Not completed";
                    //         if (($pending_activites->count()==0 && $total_activites->count()==0)) {
                    //             $payslip->instructor_status = null;
                    //         }
                    //     } else {
                    //         $payslip->instructor_status = "Partially completed";
                    //     }
                    // }
                }
            }
        }
        /**End */

        $year_month = $payroll->year.($payroll->month < 10 ? "0$payroll->month" : $payroll->month);

        $payrolls = Payroll::all();
        $payrolls->transform(function ($payroll_item) {
            $payroll_item->year_month = $payroll_item->year.($payroll_item->month < 10 ? "0$payroll_item->month" : $payroll_item->month);
            return $payroll_item;
        });

        $last_payroll = $payrolls->sortByDesc('year_month')->where('year_month', '<', $year_month)->first();
        $previous = json_decode("{}");
        if ($last_payroll) {
            $month = $last_payroll->month < 10 ? "0$last_payroll->month" : $last_payroll->month;
            $month_name = date('F', strtotime('1995'.$month.'05'));

            $previous = [
                'text' => $month_name.' '.$last_payroll->year,
                'id' => $last_payroll->id
            ];
        }

        $next_payroll = $payrolls->sortByDesc('year_month')->where('year_month', '>', $year_month)->last();
        $next = json_decode("{}");
        if ($next_payroll) {
            $month = $next_payroll->month < 10 ? "0$next_payroll->month" : $next_payroll->month;
            $month_name = date('F', strtotime('1995'.$month.'05'));
            $next = [
                'text' => $month_name.' '.$next_payroll->year,
                'id' => $next_payroll->id
            ];
        }

        $payroll['payslips'] = $payslips;
        $payroll['payslips_count'] = $payslips_count;

        if ($request->is_export) {
            return Excel::download(new PayrollExport($payroll->toArray()), 'Payroll.csv');
        }

        return $this->sendResponse(true, 'Payroll with payslips', [
            'payroll' => $payroll,
            'pagination' => [
                'previous' => $previous,
                'next' => $next,
            ]
        ]);
    }

    /* Get Single Payroll with Payslip */
    public function getPayslip($id)
    {
        $payslip = Payslip::with('contact.category_detail', 'contact.bank_detail', 'payroll')->find($id);

        if (!$payslip) {
            return $this->sendResponse(false, 'Payslip not found');
        }

        return $this->sendResponse(true, 'Payslip', $payslip);
    }

    /* Refresh payslip */
    public function refreshPayslip($id)
    {
        $payslip = Payslip::find($id);
        if (!$payslip) {
            return $this->sendResponse(false, 'Payslip not found');
        }

        GeneratePayslip::dispatch($payslip->payroll_id, $payslip->contact_id);

        return $this->sendResponse(true, __('strings.payslip_refresh_started'));
    }

    /* Change payment status payslip */
    public function changeStatusPayslip(Request $request)
    {
        $v = validator($request->all(), [
            'id' => 'required',
            'status' => 'in:NP,IP,P'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $payslip = Payslip::find($request->id);
        if (!$payslip) {
            return $this->sendResponse(false, 'Payslip not found');
        }

        $payslip->update([
            'status'=> $request->status
        ]);

        return $this->sendResponse(true, __('strings.payslip_status_changed'));
    }

    /* Update Single Payslip */
    public function updatePayslip(Request $request)
    {
        $v = validator($request->all(), [
            'id' => 'required',
            'settlement_amount' => 'numeric|nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $payslip = Payslip::find($request->id);

        if (!$payslip) {
            return $this->sendResponse(false, 'Payslip not found');
        }

        $update_data = $request->only('settlement_amount', 'settlement_description', 'check_number', 'ref_number', 'payment_type', 'comments');
        $payslip->update($update_data);

        GeneratePayslip::dispatch($payslip->payroll_id, $payslip->contact_id);

        return $this->sendResponse(true, __('strings.payslip_updated_success'));
    }

    public function downloadPayslip($id)
    {
        $payslip = Payslip::with('contact.category_detail', 'contact.bank_detail', 'payroll')->find($id);

        if (!$payslip) {
            return $this->sendResponse(false, 'Payslip not found');
        }

        /* if (isset($_GET['data'])) {
            echo '<pre>';
            print_r($payslip->toArray());
            die();
        } elseif (isset($_GET['pdf'])) {
            $pdf = PDF::loadView('payslip.pdf', ['payslip'=>$payslip]);

            return $pdf->download('test.pdf');
        }
        return view('payslip.pdf', ['payslip'=>$payslip]); */

        $pdf = PDF::loadView('payslip.pdf', ['payslip'=>$payslip]);

        return $pdf->download($payslip->contact->first_name.' '.date('F, Y', strtotime($payslip->payroll->year.$payslip->payroll->month.'01')).'.pdf');
    }

    public function emailPayslip($id)
    {
        $payslip = Payslip::with('contact.category_detail', 'contact.bank_detail', 'payroll')->find($id);

        if (!$payslip) {
            return $this->sendResponse(false, 'Payslip not found');
        }

        $data['email'] = $payslip->contact->email;
        $data['first_name'] = $payslip->contact->first_name;
        $data['full_name'] = $payslip->contact->first_name.' '.$payslip->contact->last_name;
        $data['month'] = date('F', strtotime('1995'.$payslip->payroll->month.'05'));
        $data['year'] = date('Y', strtotime($payslip->payroll->year.'05'.'05'));
        $data['subject'] = "Payslip | ".$data['month'].'-'.$data['year'];
        $data['attachment_name'] = $data['full_name'].' - '.$data['month'].', '.$data['year'].'.pdf';

        $pdf = PDF::loadView('payslip.pdf', ['payslip'=> $payslip]);

        // return view('email.payslip', $data);

        /**Get default locale and set user language locale */
        $temp_locale = \App::getLocale();
        $user = User::where('email', $data['email'])->first();
        if($user){
            \App::setLocale($user->language_locale);
        }
        /**End */
        Mail::send('email.payslip', $data, function ($message) use ($data,$pdf) {
            $message->to($data['email'], $data['full_name'])
            ->subject($data['subject'])
            ->attachData($pdf->output(), $data['attachment_name']);
        });
        /**Set default language locale */
        \App::setLocale($temp_locale);

        return $this->sendResponse(true, 'Email sent successfully.');
    }

    /* Get Contact (Instructor / Employee) Latest Payslips */
    public function getContactPlaySlips($id)
    {
       
        // get active contacts
        $contact = Contact::find($id);
        if(!$contact)
        {
            return $this->sendResponse(false, 'Contact not found');
        }

        $payslip = Payslip::where('contact_id',$id)->with('contact.category_detail', 'contact.bank_detail', 'payroll')->orderBy('id', 'desc')->take(5)->get();

        if (!$payslip) {
            return $this->sendResponse(false, 'Payslip not found');
        }

        return $this->sendResponse(true, 'Payslip', $payslip);


    }

    /* Check Any Insructor his Activity Timesheet Confirm Or Not */
    public function checkInstructorActivityTimesheetPayroll(Request $request)
    {
        $v = validator($request->all(), [
            'year'         => 'required|integer|min:1970|max:2100',
            'month'        => 'required|date_format:m',
            'working_days' => 'required|integer|min:1|max:31'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $year = $request->year;
        $month = $request->month;

        $time = strtotime("$year-$month-01");
        $first_day = date("Y-m-d", $time);
        $last_day = date("Y-m-t", $time);

        // get active contacts (instructors, employees)
        $contacts = Contact::whereIn('category_id', ['2','3'])->where('is_active', '1')->get();
        $result_contacts= array();
        foreach ($contacts as $contact) {
            if($contact->user_detail){
                //Get Contact Activity Timesheet
                $activites = $contact->user_detail->timesheets
                        ->where('activity_date', '>=', $first_day)
                        ->where('activity_date', '<=', $last_day);
    
                if ($activites->count()) {
                    foreach ($activites as $activity) {
                       if ($activity->status === 'P' || $activity->status === 'IP' || $activity->status === 'R') {
                        $result_contacts[] = $contact;
                        unset($contact['user_detail']['timesheets']);
                       }                    
                    }
                }   
            }
        }

        return $this->sendResponse(true, 'success', array_unique($result_contacts));

    }   

    /* Get Payroll Instructor Activity Timesheet */
    public function getPayrollContactActivityTimeSheet(Request $request)
    {

        $v = validator($request->all(), [
            'year'         => 'required|integer|min:1970|max:2100',
            'month'        => 'required|date_format:m',
            'contact_id' => 'required|integer',
            'page' => 'required|integer|min:1',
            'perPage' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $page = $request->page;    
        $perPage = $request->perPage;    
        $year = $request->year;
        $month = $request->month;

        $time = strtotime("$year-$month-01");
        $first_day = date("Y-m-d", $time);
        $last_day = date("Y-m-t", $time);

        // get active contacts
        $contact = Contact::find($request->contact_id);
        if(!$contact)
        {
            return $this->sendResponse(false, 'Contact not found');
        }
                
        //get activites list based on payroll
        $activites= InstructorActivityTimesheet::where('instructor_id',$contact->user_detail->id)->where('activity_date', '>=', $first_day)
        ->where('activity_date', '<=', $last_day);

        // $activites = $contact->user_detail->timesheets
        // ->where('activity_date', '>=', $first_day)
        // ->where('activity_date', '<=', $last_day);

        $activites_count = $activites->count();
        $activites->skip($perPage*($page-1))->take($perPage);

        $activites = $activites->with('instructor_detail')->with('booking_detail.course_detail.course_data')->get();

        $data = [
            'activities' => $activites,
            'count' => $activites_count
        ];

        return $this->sendResponse(true,'success',$data);
    }

    /* Get Contact (Instructor) Latest Payslips */
    public function paySlipsWithPagination(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $instructor_id = auth()->user()->contact_id;

        $instructor = Contact::find($instructor_id)->where('category_id', 2)->first();
        if(!$instructor)
        {
            return $this->sendResponse(false, 'Instructor not found');
        }

        $payslip = Payslip::where('contact_id',$instructor_id);
        
        if($request->page && $request->perPage){
            $page = $request->page;
            $perPage = $request->perPage;
            $payslip = $payslip->skip($perPage*($page-1))->take($perPage);
        }
        $payslip_count = $payslip->count();

        $payslip = $payslip->with('contact.category_detail', 'contact.bank_detail', 'payroll')->orderBy('id', 'desc')->get();

        $data = [
            'payslip' => $payslip,
            'count' => $payslip_count
        ];

        return $this->sendResponse(true, 'Payslip', $data);
    }

    /* Check Any Insructor his Activity Timesheet Confirm Or Not */
    public function sendNotificationInstructorTimesheetPending(Request $request)
    {
        $v = validator($request->all(), [
            'year'         => 'required|integer|min:1970|max:2100',
            'month'        => 'required|date_format:m'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $year = $request->year;
        $month = $request->month;

        $time = strtotime("$year-$month-01");
        $first_day = date("Y-m-d", $time);
        $last_day = date("Y-m-t", $time);

        //Get active contacts (instructors)
        $contacts = Contact::whereIn('category_id', ['2'])->where('is_active', '1')->get();
        $result_contacts= array();
        $i = 0;
        foreach ($contacts as $contact) {
            if($contact->user_detail){
                //Get Contact Activity Timesheet
                $activites = $contact->user_detail->timesheets
                        ->where('activity_date', '>=', $first_day)
                        ->where('activity_date', '<=', $last_day);
    
                if ($activites->count()) {
                    foreach ($activites as $activity) {
                       if ($activity->status === 'P' || $activity->status === 'IP' || $activity->status === 'R') {
                        $result_contacts[] = $contact['user_detail'];
                       }                    
                    }
                }   
            }
            $i = $i + 1;
        }
        $result_contacts = array_unique($result_contacts);
        foreach($result_contacts as $contact){
            $receiver_id = $contact['contact_id'];
            $sender_id = null;//Admin
            $type = 33;//For instructor pending activity time sheet
            $title = "Please Approved your year-".$year." month time sheets : ".$month." activities have already pending!";
            $body = "Please Approved your year-".$year." month time sheets : ".$month." activities have already pending!";
            $data = array(); 

            $notification = \App\Models\Notification::create(['sender_id'=>$sender_id,"receiver_id"=>$receiver_id,"type"=>$type,'message'=>$body]);
            if ($contact['is_notification'] == 1) {
                if (!empty($contact['device_token'])) {
                    SendPushNotification::dispatch($contact['device_token'], $contact['device_type'], $title, $body, $type, $data);
                }
            }
        }
        return $this->sendResponse(true, __('strings.sent_success',['name' => 'Instructor activity pending reminder']));
    }
}
