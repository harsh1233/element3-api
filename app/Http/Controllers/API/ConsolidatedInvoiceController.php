<?php

namespace App\Http\Controllers\API;

use PDF;
use Mail;
use Excel;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Exports\ConsolidatedInvoiceExport;
use App\Models\BookingProcess\ConsolidatedInvoice;
use App\Models\BookingProcess\ConsolidatedInvoiceProduct;
use App\Models\BookingProcess\BookingProcessPaymentDetails;

class ConsolidatedInvoiceController extends Controller
{
    use Functions;

    /**Generate consolidated invoice */
    public function create(Request $request)
    {
        $v = validator($request->all(), [
            /**Rules */
            'name' => 'required|max:50',
            'address' => 'required|max:191',
            'total_amount' => 'required',
            'grant_amount' => 'required',
            'emails' => 'required|array',
            'invoices' => 'required|array',
            'vat' => 'nullable|numeric|min:1',
            'settlement_amount' => 'nullable',
            'settlement_description' => 'nullable',
            'product_details' => 'array'
        ]);

        /**Check above validation and if any mismatch then return error response */
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        
        /**Check if media exist in another media master then return error */
        $invoice_ids = ConsolidatedInvoice::pluck('invoices')->toArray();
        $invoices = $request->invoices;
        $selected_invoices = array();

        foreach($invoice_ids as $invoice){
            foreach($invoice as $id){
                $selected_invoices[] = $id;
            }
        }

        $selected_invoices = array_unique($selected_invoices);

        $common_ids = array_intersect($selected_invoices, $invoices);

        $selected_invoice_numbers = BookingProcessPaymentDetails::whereIn('id', $common_ids)
            ->pluck('invoice_number')->toArray();

        $selected_invoice_numbers = implode(', ',$selected_invoice_numbers);

        if (count($common_ids)) {
            return $this->sendResponse(false, __('strings.invoices_already_assigned', ['invoice_numbers' => $selected_invoice_numbers]));
        }

        /**Get necessary details */
        $input_details = $request->only('name', 'address', 'total_amount', 'grant_amount','emails', 'invoices', 'settlement_amount' , 'settlement_description');

        $vat_percentage = $this->getVat();
        if($request->vat){
            $vat_percentage = $request->vat;
        }
        //excluding vat amount calculation
        $vat_amount = $input_details['total_amount'] * $vat_percentage / 100;

        //vat amount calculation
        $vat_excluded_amount = $input_details['total_amount'] - $vat_amount;
        
        $input_details['vat_percentage'] = $vat_percentage;
        $input_details['vat_amount'] = $vat_amount;
        $input_details['vat_excluded_amount'] = $vat_excluded_amount;

        $consolidated_invoice = ConsolidatedInvoice::create($input_details);

        /**Add consolidate product details */
        if($request->product_details){
            $i = 0;
            foreach($request->product_details as $product){
                $product_details[$i]['consolidated_invoice_id'] = $consolidated_invoice->id;
                $product_details[$i]['product_name'] = $product['name'];
                $product_details[$i]['product_price'] = $product['product_price'];
                $product_details[$i]['product_vat'] = $product['product_vat'];
                
                if(isset($product['product_grant_amount']) && $product['product_grant_amount']){
                    $product_grant_amount = $product['product_grant_amount'];
                }else{
                    $product_grant_amount = $product['product_price'] + ($product['product_price'] * $product['product_vat'] / 100);
                }

                $product_details[$i]['grant_amount'] = $product_grant_amount;
                $product_details[$i]['created_at'] = date("Y-m-d H:i:s");
                $product_details[$i]['created_by'] = auth()->user()->id;
                $i = $i + 1;
            }
            ConsolidatedInvoiceProduct::insert($product_details);
        }
        /**End */

        $payment_details = BookingProcessPaymentDetails::whereIn('id', $input_details['invoices'])
        ->get();
        $invoice_numbers = array();
        foreach($payment_details as $payment){
            if($payment['status'] != 'Success'){
                $exploded_invoice_number = explode('INV', $payment['invoice_number']);
                $invoice_numbers[] = (isset($exploded_invoice_number[1]) ? $exploded_invoice_number[1] : '');
            }else{
                $invoice_numbers[] = $payment['invoice_number'];
            }
        }
        $invoice_numbers = implode(", ", $invoice_numbers);

        /**Generate consolidated invoice */
        $this->generateConsolidatedInvoice($consolidated_invoice->id, $invoice_numbers);

        /**Send consolidated invoice */
        if($consolidated_invoice->consolidated_receipt){
            /**Send email */
            foreach($input_details['emails'] as $email){
                /**Get default locale and set user language locale */
                $temp_locale = \App::getLocale();
                $user = User::where('email', $email)->first();
                if($user){
                    \App::setLocale($user->language_locale);
                }
                /**End */

                $mail = new Mail();
                
                $input_details['invoice_numbers'] = $invoice_numbers;
                $consolidated_invoice_receipt = $consolidated_invoice->consolidated_receipt;

                $mail::send('email.consolidated_invoice', $input_details, function ($message) use ($consolidated_invoice_receipt,$email,$invoice_numbers, $input_details) {
                    $message->to($email)
                        ->subject(__('email_subject.consolidated_booking_invoice', ['name' => $input_details['name'],'created_at' => date('h:i a')]));
                        $message->attachData(file_get_contents($consolidated_invoice_receipt), $input_details['name']."_consolidated_invoice.pdf");
                });

                /**Set default language locale */
                \App::setLocale($temp_locale);
            }
        }

        /**Add crm user action trail */
        if ($consolidated_invoice) {
            $action_id = $consolidated_invoice->id; //consolidated id
            $action_type = 'A'; //A = Add
            $module_id = 32; //module id base module table
            $module_name = "Consolidated Invoices"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        /**Return success response */
        return $this->sendResponse(true, __('strings.create_sucess', ['name' => 'Consolidated invoice']));
    }

    /**Consolidated invoice list */
    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $consolidated_invoices = ConsolidatedInvoice::query()->latest();

        if($request->search) {
            $search = $request->search;
            $consolidated_invoices = $consolidated_invoices->where(function($query) use($search){
                $query->where('name','like',"%$search%");
                $query->orWhere('address','like',"%$search%");
                $query->orWhere('total_amount','like',"%$search%");
                $query->orWhere('emails','like',"%$search%");
                $query->orWhere('invoices','like',"%$search%");
            });
            $consolidated_invoices_count = $consolidated_invoices->count();
        }

        if($request->date) {
            $date = $request->date;
            if($request->end_date) {
                $end_date = $request->end_date;
                $consolidated_invoices = $consolidated_invoices->whereDate('created_at', '>=',$date);
                $consolidated_invoices = $consolidated_invoices->whereDate('created_at', '<=',$end_date);
            }else{
                $consolidated_invoices = $consolidated_invoices->whereDate('created_at', $date);
            }
            $consolidated_invoices_count = $consolidated_invoices->count();
        }

        $consolidated_invoices_count = $consolidated_invoices->count();
        
        if($request->page && $request->perPage){
            $page = $request->page;
            $perPage = $request->perPage;
            $consolidated_invoices = $consolidated_invoices->skip($perPage*($page-1))->take($perPage);
        }
        $consolidated_invoices = $consolidated_invoices->with('product_detail')->get();

        /**Export list */
        if ($request->is_export) {
            return Excel::download(new ConsolidatedInvoiceExport($consolidated_invoices->toArray()), 'ConsolidatedInvoice.csv');
        }

        $data = [
            'consolidated_invoices' => $consolidated_invoices,
            'count' => $consolidated_invoices_count
        ];
        /**Return success response */
        return $this->sendResponse(true, __('strings.list_message', ['name' => 'Consolidated invoice']), $data);
    }

    /**Consolidated invoice delete */
    public function delete($id)
    {
        $consolidated_invoice = ConsolidatedInvoice::find($id);

        if (!$consolidated_invoice) return $this->sendResponse(false,__('strings.not_found_validation', ['name' => 'Consolidated invoice']));

        /**Delete crm user action trail */
        if ($consolidated_invoice) {
            $action_id = $consolidated_invoice->id; //consolidated id
            $action_type = 'D'; //D = Delete
            $module_id = 32; //module id base module table
            $module_name = "Consolidated Invoices"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        $consolidated_invoice->delete();

        /**Return success response */
        return $this->sendResponse(true, __('strings.delete_sucess', ['name' => 'Consolidated invoice']));
    }

    /**Update consolidated invoice */
    public function update(Request $request, $id)
    {
        $v = validator($request->all(), [
            /**Rules */
            'name' => 'required|max:50',
            'address' => 'required|max:191',
            'total_amount' => 'required',
            'grant_amount' => 'required',
            'emails' => 'required|array',
            'invoices' => 'required|array',
            'vat' => 'nullable|numeric|min:1',
            'settlement_amount' => 'nullable',
            'settlement_description' => 'nullable',
            'product_details' => 'array',
            'product_details.*.action' => 'in:1,2',//1: Add, 2: Update
            'deleted_product_ids' => 'array'
        ],[
            'product_details.*.action.in' => __('validation.action_invalid')
        ]);

        /**Check above validation and if any mismatch then return error response */
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $consolidated_invoice = ConsolidatedInvoice::find($id);

        if (!$consolidated_invoice)
            return $this->sendResponse(false,__('strings.not_found_validation', ['name' => 'Consolidated invoice']));
        
        /**Get necessary details */
        $input_details = $request->only('name', 'address', 'total_amount', 'grant_amount','emails', 'invoices', 'settlement_amount' , 'settlement_description');

        $vat_percentage = $this->getVat();
        if($request->vat){
            $vat_percentage = $request->vat;
        }
        //excluding vat amount calculation
        $vat_amount = $input_details['total_amount'] * $vat_percentage / 100;

        //vat amount calculation
        $vat_excluded_amount = $input_details['total_amount'] - $vat_amount;
        
        $input_details['vat_percentage'] = $vat_percentage;
        $input_details['vat_amount'] = $vat_amount;
        $input_details['vat_excluded_amount'] = $vat_excluded_amount;

        $consolidated_invoice->update($input_details);

        /**Add/ Update/ Delete consolidate product details */
        if($request->product_details){
            $i = 0;
            $product_input = array();
            
            foreach($request->product_details as $product){
                
                if(isset($product['product_grant_amount']) && $product['product_grant_amount']){
                    $product_grant_amount = $product['product_grant_amount'];
                }else{
                    $product_grant_amount = $product['product_price'] + ($product['product_price'] * $product['product_vat'] / 100);
                }

                if($product['action'] === 1){
                    $product_input[$i]['product_name'] = $product['name'];
                    $product_input[$i]['product_price'] = $product['product_price'];
                    $product_input[$i]['product_vat'] = $product['product_vat'];
                    $product_input[$i]['consolidated_invoice_id'] = $id;
                    $product_input[$i]['grant_amount'] = $product_grant_amount;
                    $product_input[$i]['created_at'] = date("Y-m-d H:i:s");
                    $product_input[$i]['created_by'] = auth()->user()->id;
                }elseif($product['action'] === 2){
                    $product_exist = ConsolidatedInvoiceProduct::where('id', $product['id'])
                    ->where('consolidated_invoice_id', $id)->first();
                    if(!$product_exist){
                        return $this->sendResponse(false, __('strings.not_found_validation', ['name' => 'Consolidated product'])); 
                    }
                    $update_details['product_name'] = $product['name'];
                    $update_details['product_price'] = $product['product_price'];
                    $update_details['product_vat'] = $product['product_vat'];
                    $update_details['grant_amount'] = $product_grant_amount;
                    $update_details['updated_at'] = date("Y-m-d H:i:s");
                    $update_details['updated_by'] = auth()->user()->id;
                    $product_exist->update($update_details);
                }
                
                $i = $i + 1;
            }
            if(count($product_input)){
                ConsolidatedInvoiceProduct::insert($product_input);
            }
        }
        
        if($request->deleted_product_ids && count($request->deleted_product_ids)){
            $product_exist = ConsolidatedInvoiceProduct::whereIn('id', $request->deleted_product_ids)
            ->where('consolidated_invoice_id', $id)->first();
            if(!$product_exist){
                return $this->sendResponse(false, __('strings.not_found_validation', ['name' => 'Consolidated products'])); 
            }
            ConsolidatedInvoiceProduct::destroy($request->deleted_product_ids);
        }
        /**End */
        
        $payment_details = BookingProcessPaymentDetails::whereIn('id', $input_details['invoices'])
        ->get();
        $invoice_numbers = array();
        
        foreach($payment_details as $payment){
            if($payment['status'] != 'Success'){
                $exploded_invoice_number = explode('INV', $payment['invoice_number']);
                $invoice_numbers[] = (isset($exploded_invoice_number[1]) ? $exploded_invoice_number[1] : '');
            }else{
                $invoice_numbers[] = $payment['invoice_number'];
            }
        }
        $invoice_numbers = implode(", ", $invoice_numbers);

        /**Generate consolidated invoice */
        $this->generateConsolidatedInvoice($id, $invoice_numbers);

        /**Send consolidated invoice */
        $consolidated_invoice = ConsolidatedInvoice::find($id);

        if($consolidated_invoice->consolidated_receipt){
            /**Send email */
            foreach($input_details['emails'] as $email){
                /**Get default locale and set user language locale */
                $temp_locale = \App::getLocale();
                $user = User::where('email', $email)->first();
                if($user){
                    \App::setLocale($user->language_locale);
                }
                /**End */
                $mail = new Mail();
                $input_details['invoice_numbers'] = $invoice_numbers;
                $consolidated_invoice_receipt = $consolidated_invoice->consolidated_receipt;

                $mail::send('email.consolidated_invoice', $input_details, function ($message) use ($consolidated_invoice_receipt,$email,$invoice_numbers, $input_details) {
                    $message->to($email)
                    ->subject(__('email_subject.consolidated_booking_invoice', ['name' => $input_details['name'],'created_at' => date('h:i a')]));
                    $message->attachData(file_get_contents($consolidated_invoice_receipt), $input_details['name']."_consolidated_invoice.pdf");
                });
                /**Set default language locale */
                \App::setLocale($temp_locale);
            }
        }

        /**Update crm user action trail */
        if ($consolidated_invoice) {
            $action_id = $consolidated_invoice->id; //consolidated id
            $action_type = 'U'; //U = Update
            $module_id = 32; //module id base module table
            $module_name = "Consolidated Invoices"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        /**Return success response */
        return $this->sendResponse(true, __('strings.update_sucess', ['name' => 'Consolidated invoice']));
    }

    /**Dowenload consolidated invoice url
     * Date : 16-09-2020
    */
    public function generateConsolidatedReceipt($id)
    {
        $consolidated_details = ConsolidatedInvoice::find($id);

        $payment_details = BookingProcessPaymentDetails::whereIn('id', $consolidated_details->invoices)
        ->get();
        $invoice_numbers = array();
        foreach($payment_details as $payment){
            if($payment['status'] != 'Success'){
                $exploded_invoice_number = explode('INV', $payment['invoice_number']);
                $invoice_numbers[] = (isset($exploded_invoice_number[1]) ? $exploded_invoice_number[1] : '');
            }else{
                $invoice_numbers[] = $payment['invoice_number'];
            }
        }
        $invoice_numbers = implode(", ", $invoice_numbers);

        $data = $this->generateConsolidatedInvoice($id, $invoice_numbers);

        return $data['pdf']->download($data['name'].'_cansolidated_invoice.pdf');
    }

    /**Update payment status */
    public function updatePaymentStatus(Request $request)
    {
        $v = validator($request->all(), [
            /**Rules */
            'payment_method_id' => 'required|exists:payment_methods,id' ,
            'invoices' => 'required|array',
            'office_id' => 'required|exists:offices,id',
            'consolidated_invoice_id' => 'required|exists:consolidated_invoices,id',
            'credit_card_type' => 'nullable|integer'
        ]);

        /**Check above validation and if any mismatch then return error response */
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $consolidated_invoice = ConsolidatedInvoice::find($request->consolidated_invoice_id);
    
        if(!$consolidated_invoice){
            return $this->sendResponse(false, __('strings.not_found_validation', ['name' => 'Consolidated invoice']));
        }

        $total_vat = BookingProcessPaymentDetails::whereIn('id', $request->invoices)->sum('vat_amount');
        $is_reverse_charge = false;
        $vat_amount = $consolidated_invoice->vat_amount;
        $grant_consolidated=0;
        if($request->is_reverse_charge){
            $is_reverse_charge = true;
            $vat_amount = 0;
            $grant_consolidated=$consolidated_invoice->grant_amount - $total_vat;
        }
        else
        {
            $grant_consolidated=$consolidated_invoice->grant_amount;
        }

        $payment_details = [
            "qbon_number"               => null,
            "contact_id"                => 0,
            "payment_type"              => $request->payment_method_id,
            "is_office"                 => 1,
            "office_id"                 => $request->office_id,
            "amount_given_by_customer"  => $grant_consolidated,
            "amount_returned"           => 0,
            "total_amount"              => $grant_consolidated,
            "total_discount"            => 0,
            "total_vat_amount"          => $vat_amount,
            "total_net_amount"          => $grant_consolidated,
            "is_threw_consolidated"     => true,
            "consolidated_invoice_id"   => $request->consolidated_invoice_id,
            'is_reverse_charge'         => $is_reverse_charge,
            "credit_card_type"          => ($request->credit_card_type?:null),
            'total_vat'                 => $total_vat,
            'cash_amount'               => ($request->cash_amount?:null),
            'creditcard_amount'         => ($request->creditcard_amount?:null)
        ];
        $i = 0;
        foreach($request->invoices as $invoice){
            $booking_payment = BookingProcessPaymentDetails::find($invoice);
            $invoice_data[$i]['total_price'] = $booking_payment->total_price;
            $invoice_data[$i]['discount'] = $booking_payment->discount?$booking_payment->discount:0;
            $invoice_data[$i]['vat_amount'] = $booking_payment->vat_amount;
            $invoice_data[$i]['vat_excluded_amount'] = $booking_payment->vat_excluded_amount;
            $invoice_data[$i]['net_price'] = $booking_payment->net_price;
            $invoice_data[$i]['id'] = $invoice;
            $i = $i + 1;
        }
        
        DB::beginTransaction();
        try {
            // Create payment
            $payment = $this->createPayment($payment_details, $invoice_data);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            /**For save exception */
            $this->saveException($e, $request->header(), $request->ip());
        }

        $consolidated_invoice->payment_method_id = $request->payment_method_id;
        $consolidated_invoice->save();

        /**Return success response */
        return $this->sendResponse(true, __('strings.update_sucess', ['name' => 'Consolidated invoice']));
    }

    /**Send again consolidated invoice */
    public function sendAgainInvoice(Request $request)
    {
        $v = validator($request->all(), [
            /**Rules */
            'consolidated_id' => 'required|exists:consolidated_invoices,id'
        ]);

        /**Check above validation and if any mismatch then return error response */
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        /**Send consolidated email */
        $this->consolidateInvoiceSend($request->consolidated_id);

        /**Return success response */
        return $this->sendResponse(true, __('strings.sent_success', ['name' => 'Consolidated invoice']));
    }

    /**Send consolidated invoice email */
    public function consolidateInvoiceSend($consolidated_id, $invoice_numbers = null, $input_details = array())
    {
        $consolidated_invoice = ConsolidatedInvoice::find($consolidated_id);

        if(!$invoice_numbers){
            $payment_details = BookingProcessPaymentDetails::whereIn('id', $consolidated_invoice->invoices)
            ->get();
            $invoice_numbers = array();
            foreach($payment_details as $payment){
                if($payment['status'] != 'Success'){
                    $exploded_invoice_number = explode('INV', $payment['invoice_number']);
                    $invoice_numbers[] = (isset($exploded_invoice_number[1]) ? $exploded_invoice_number[1] : '');
                }else{
                    $invoice_numbers[] = $payment['invoice_number'];
                }
            }
            $invoice_numbers = implode(", ", $invoice_numbers);
        }

        if(!count($input_details)){
            $input_details['name'] = $consolidated_invoice->name;
            $input_details['address'] = $consolidated_invoice->address;
        }
        
        if($consolidated_invoice->consolidated_receipt){
            /**Send email */
            foreach($consolidated_invoice->emails as $email){
                /**Get default locale and set user language locale */
                $temp_locale = \App::getLocale();
                $user = User::where('email', $email)->first();
                if($user){
                    \App::setLocale($user->language_locale);
                }
                /**End */
                $mail = new Mail();
                $input_details['invoice_numbers'] = $invoice_numbers;
                $consolidated_invoice_receipt = $consolidated_invoice->consolidated_receipt;

                $mail::send('email.consolidated_invoice', $input_details, function ($message) use ($consolidated_invoice_receipt,$email,$invoice_numbers, $input_details) {
                    $message->to($email)
                        ->subject(__('email_subject.consolidated_booking_invoice', ['name' => $input_details['name'],'created_at' => date('h:i a')]));
                        $message->attachData(file_get_contents($consolidated_invoice_receipt), $input_details['name']."_consolidated_invoice.pdf");
                });
                /**Set default language locale */
                \App::setLocale($temp_locale);
            }
        }
        return;
    }
}
