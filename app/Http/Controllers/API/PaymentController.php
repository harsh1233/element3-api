<?php

namespace App\Http\Controllers\API;

use Excel;
use Carbon\Carbon;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Models\AccountMaster;
use App\Exports\PaymentExport;
use App\Models\SequenceMaster;
use App\Models\CreditCardMaster;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\BookingProcess\BookingPayment;
use App\Models\BookingProcess\BookingProcessPaymentDetails;

class PaymentController extends Controller
{
    use Functions;

    public function list(Request $request)
    {
        $v = validator($request->all(), [
           // 'start_date' => 'nullable|date_format:Y-m-d',
           // 'end_date' => 'nullable|date_format:Y-m-d',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $payments = BookingPayment::with('payee_detail', 'invoice_detail', 'payment_type_detail', 'credit_card_detail');

        if ($request->payment_number) {
            $payment_number = $request->payment_number;
            $payments = $payments->where('payment_number', 'like', "%$payment_number%");
        }
        if ($request->qbon_number) {
            $qbon_number = $request->qbon_number;
            $payments = $payments->where('qbon_number', 'like', "%$qbon_number%");
        }
        if ($request->payment_type) {
            $payment_type = $request->payment_type;
            $payments = $payments->where('payment_type', $payment_type);
        }

        if ($request->invoice_number) {
            $invoice_number = $request->invoice_number;
            $paymentIds =  BookingProcessPaymentDetails::where('invoice_number', $invoice_number)->pluck('payment_id');
            $payments = $payments->whereIn('id', $paymentIds);
        }

        if ($request->payee) {
            $payee = $request->payee;
            $contactIds = Contact::where(function ($query) use ($payee) {
                $query->where('salutation', 'like', "%$payee%");
                $query->orWhere('first_name', 'like', "%$payee%");
                $query->orWhere('middle_name', 'like', "%$payee%");
                $query->orWhere('last_name', 'like', "%$payee%");
            })->pluck('id');
            $payments = $payments->whereIn('contact_id', $contactIds);
        }

        if ($request->start_date && $request->end_date) {
            $start_date = $request->start_date;
            $end_date = $request->end_date;
            $payments = $payments->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date);
        }

        if ($request->duration) {
            $duration = $request->duration;
            $today= Carbon::today();
            if($request->duration==='year')
            {
                $date = Carbon::today()->subDays(365);
            }
            else if($request->duration==='month')
            {
                $date = Carbon::today()->subDays(30);
            }
            else if($request->duration==='week')
            {
                $date = Carbon::today()->subDays(7);
            }
            
            if($request->duration==='today')
            {
                $payments = $payments->whereDate('created_at', Carbon::today());
            }
            else if($date && $today)
            {
              $payments = $payments->where('created_at', '>=', $date)->where('created_at', '<=', $today);
            }
            
        }

        $payments = $payments->orderBy('id', 'desc')->get();

        //for export file if is_export passed
        if ($request->is_export) {
            $i = 0;
            $payment_amonut = 0;
            $payment_type_base = 0;
            $payment_card_type = array();
            $payment_method = array();
            $payment_card_base_details = array();
            $payment_method_base_details = array();
            foreach($payments as $payment){
                if(in_array($payment['payment_card_brand'], $payment_card_type)){
                    $payment_amonut = $payment_amonut + $payment['total_net_amount'];
                }else{
                    $payment_card_type[] = $payment['payment_card_brand']; 
                    $payment_amonut = 0;
                    $payment_amonut = $payment_amonut + $payment['total_net_amount'];
                }

                if(in_array($payment['payment_type'], $payment_method)){
                    $payment_type_base = $payment_type_base + $payment['total_net_amount'];
                }else{
                    $payment_method[] = $payment['payment_type']; 
                    $payment_type_base = 0;
                    $payment_type_base = $payment_type_base + $payment['total_net_amount'];
                }
                $payment_method_base_details[$payment['payment_type_detail']['type']] = $payment_type_base;

                if(!$payment['payment_card_brand']){
                    continue;
                }else{
                    $payment_card_base_details[$payment['payment_card_brand']]['total_payment_amonut'] = $payment_amonut;
                }

                $i = $i + 1;
            }
            return Excel::download(new PaymentExport($payments->toArray(), $payment_card_base_details, $payment_method_base_details), 'Payments.csv');
        }

        return $this->sendResponse(true, 'Payments.', $payments);
    }

    public function getRecordPaymentData(Request $request)
    {
        $v = validator($request->all(), [
            'payee_id' => 'required',
            'ids' => 'array',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $customer = Contact::with('address')->find($request->payee_id);
        if (!$customer) {
            return $this->sendResponse(false, 'Customer not found.');
        }
        if (!$customer->isType('Customer')) {
            return $this->sendResponse(false, 'Payee needs to be a customer.');
        }
        $invoices = BookingProcessPaymentDetails::where('payi_id', $customer->id)->with('voucher')
            ->whereIn('status', ['Pending','Failed','Outstanding']);
        if ($request->ids) {
            $invoices->whereIn('id', $request->ids);
        }
        $invoices = $invoices->with(['lead_datails.course_data' => function($q){
            $q->select('id','name','name_en','type','meeting_point_id','restricted_start_date','restricted_end_date','restricted_no_of_days','restricted_start_time','restricted_end_time','restricted_no_of_hours','notes','notes_en');
        }]);

        $invoices = $invoices->get();

        return $this->sendResponse(true, 'Record payment data.', [
            'customer' => $customer,
            'invoices' => $invoices
        ]);
    }

    public function recordPayment(Request $request)
    {
        $is_reverse_charge = false;
        if($request->is_reverse_charge){
            $is_reverse_charge = true;
        }
        $credit_card_type = null;
        if($request->credit_card_type){
            $credit_card_type = $request->credit_card_type;
        }
        $payment_details = [
            "office_id"                 => $request->office_id,
            "qbon_number"               => $request->qbon_number,
            "contact_id"                => $request->contact_id,
            "payment_type"              => $request->payment_type,
            "is_office"                 => $request->is_office,
            "amount_given_by_customer"  => $request->amount_given_by_customer,
            "amount_returned"           => $request->amount_returned,
            "total_amount"              => $request->total_amount,
            "total_discount"            => $request->total_discount,
            "total_vat_amount"          => $request->total_vat_amount,
            "total_net_amount"          => $request->total_net_amount,
            'is_reverse_charge'         => $is_reverse_charge,
            "credit_card_type"          => $credit_card_type,
            'cash_amount'               => $request->cash_amount,
            'creditcard_amount'         => $request->creditcard_amount
        ];

        DB::beginTransaction();
        try {
            // call common function to create payment
            $payment = $this->createPayment($payment_details, $request->invoice_data);
            DB::commit();
            return $this->sendResponse(true, __('strings.payment_record_success'), $payment);
        } catch (\Exception $e) {
            DB::rollback();
            /**For save exception */
            $this->saveException($e, $request->header(), $request->ip());
        }
    }
    //Payment Dashboard Chart 
    public function getChartPayment(Request $request)
    {
        // $payments = BookingPayment::groupBy(function($d) {
        //     return Carbon::parse($d->created_at)->format('d');
        // })->get();

        //get booking all payment
        $payments = BookingPayment::all();

        //Search Duration Based Date
        if($request->duration==='year')
        {
            $date = Carbon::today()->subDays(365);
        }
        else if($request->duration==='month')
        {
            $date = Carbon::today()->subDays(30);
        }
        else if($request->duration==='week')
        {
            $date = Carbon::today()->subDays(7);
        }
        else if($request->duration==='today')
        {
            $date = Carbon::today();
        }
        else
        {
            $date = Carbon::today()->subDays(7);
        }
        
        //get today date
        $today= Carbon::today();
        if($request->duration==='today')
        {
            //dd($today->toDateString());
            //exit;
            $payments = BookingPayment::whereDate('created_at', Carbon::today())->get();
        }
        else
        {
            $payments = $payments->where('created_at', '>=', $date)->where('created_at', '<=', $today);
        }
        

        //dd($payments);

        if($request->type==='card')
        {
            //get card wise total
            $total_net_amount=$payments->where('payment_type',7)->sum('total_net_amount');
            $visa_card_payments=$payments->where('payment_card_brand','VISA')->sum('total_net_amount');
            $master_card_payments=$payments->where('payment_card_brand','MasterCard')->sum('total_net_amount');
            $maestro_card_payments=$payments->where('payment_card_brand','Maestro')->sum('total_net_amount');
            // $amex_card_payments=$payments->where('payment_card_brand','AMEX')->sum('total_net_amount');
            // $jcb_card_payments=$payments->where('payment_card_brand','JCB')->sum('total_net_amount');
            // $discover_card_payments=$payments->where('payment_card_brand','DISCOVER')->sum('total_net_amount');

            $cards=[];
            $cards['Visa']=round($visa_card_payments);
            $cards['Master Card']=round($master_card_payments);
            $cards['Maestro']=round($maestro_card_payments);
            // $cards['Amex']=round($amex_card_payments);
            // $cards['JCB']=round($jcb_card_payments);
            // $cards['Discover']=round($discover_card_payments);

            $data = [
                'total_net_amount' => $total_net_amount,
                'payments' => array($cards)
            ];
        }
        elseif($request->type==='card_offline'){
            //get credit card type wise total, 5 : Credit Card
            $total_net_amount=$payments->where('payment_type',5)->sum('total_net_amount');
            
            $credit_card_master_types = CreditCardMaster::get();
            
            $cards_offline=[];
            foreach($credit_card_master_types as $credit_card_type){
                $credit_card_total = $payments->where('credit_card_type', $credit_card_type->id)->sum('total_net_amount');
                $cards_offline[$credit_card_type->name]=round($credit_card_total);
            }

            $data = [
                'total_net_amount' => $total_net_amount,
                'payments' => array($cards_offline)
            ];
        }else{
            //get payment method wise total
            $total_net_amount=$payments->sum('total_net_amount');
            $cash_payments=$payments->where('payment_type',3)->sum('total_net_amount');
            $bank_transfer=$payments->where('payment_type',4)->sum('total_net_amount');
            $credit_cards=$payments->where('payment_type',5)->sum('total_net_amount');
            $concardis_payments=$payments->where('payment_type',7)->sum('total_net_amount');
            $oncredit=$payments->where('payment_type',8)->sum('total_net_amount');
            
            $payments=[];
            $payments['Cash']=round($cash_payments);
            $payments['Bank Transfer']=round($bank_transfer);
            //$payments['Online (Concardis)']=round($concardis_payments);
            $payments['Online']=round($concardis_payments);
            $payments['Credit Card']=round($credit_cards);
            $payments['On Credit']=round($oncredit);
            
            $data = [
                'total_net_amount' => $total_net_amount,
                'payments' => array($payments)
            ];
        }

        //print_r($data);

        return $this->sendResponse(true, 'success', $data);
        
    }
}
