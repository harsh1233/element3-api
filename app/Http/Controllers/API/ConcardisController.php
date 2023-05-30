<?php

namespace App\Http\Controllers\API;

use App\Models\Office;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\Models\SequenceMaster;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\ConcardisTransaction;
use App\Models\BookingProcess\BookingProcesses;
use App\Models\BookingProcess\BookingProcessPaymentDetails;

class ConcardisController extends Controller
{
    use Functions;

    /* temp function to generate uuids for all the invoices */
    public function generateInvoiceUUID()
    {
        $invoices = BookingProcessPaymentDetails::withTrashed()->get();
        foreach ($invoices as $invoice) {
            $uuid = Str::random(50);
            $url = config('constants.crm_payment_page').'/'.$uuid;
            $invoice->update([
                'uuid' => $uuid,
                'payment_link' =>$url
            ]);
        }

        return $this->sendResponse(true, 'success ;)');
    }

    /* unauthenticated api to get booking details from uuid */
    public function getBookingDetails($uuid)
    {
        $invoice = BookingProcessPaymentDetails::where('uuid', $uuid)->first();

        if (!$invoice) {
            return $this->sendResponse(false, 'Invoice not found');
        }

        $invoice = $invoice->load(['booking_detail.course_detail.course_data'])
            ->load('course_detail')
            ->load(['customer','payi_detail','payment_detail', 'voucher']);

        if ($invoice->booking_detail->QR_number) {
            $url = url('/').'/bookingProcessQr/'.$invoice->booking_detail->QR_number;
            $qr_code = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=".$url."&choe=UTF-8";
            $invoice->booking_detail->qr_code = $qr_code;
        }
        return $this->sendResponse(true, 'success', $invoice);
    }

    public function sign(Request $request)
    {
        $v = validator($request->all(), [
            'invoice_id' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $invoice = BookingProcessPaymentDetails::with('payi_detail')->find($request->get('invoice_id'));

        if (!$invoice) {
            return $this->sendResponse(false, 'Invoice not found');
        }

        if ($invoice->payment_id) {
            return $this->sendResponse(false, 'Invoice is already paid.');
        }

        if (!$invoice->payi_detail) {
            return $this->sendResponse(false, 'Payee for this invoice not found.');
        }

        // static fields
        $fields['PSPID']    = config('constants.concardis.PSPID');
        $fields['LANGUAGE'] = config('constants.concardis.LANGUAGE');
        $fields['CURRENCY'] = config('constants.concardis.CURRENCY');

        // urls
        $fields['ACCEPTURL']  = route('concardis.accept');
        $fields['DECLINEURL']  = route('concardis.decline');
        $fields['EXCEPTIONURL']  = route('concardis.exception');
        $fields['CANCELURL']  = route('concardis.cancel');

        // sha in passphrase
        $passphrase = config('constants.concardis.SHA_IN_PASSPHRASE');

        // fields on the basis of booking invoice
        $fields['CN']       = $invoice->payi_detail->getFullName();
        $fields['EMAIL']    = $invoice->payi_detail->email;
        $fields['AMOUNT']   = (string)(int)round(($invoice->net_price) * 100); // need to multiply 100 to get the least possible currency

        // generate orderid
        $sequence_data = SequenceMaster::where('code', 'TXN')->first();

        $sequence = $sequence_data->sequence;
        $order_id = "TXN".date('m').date('Y').$sequence;

        $sequence_data->update([
            'sequence' => $sequence_data->sequence + 1
        ]);

        $fields['ORDERID']  = $order_id;

        // sha signing
        $fields['SHASIGN'] = $this->generateSHA($fields, $passphrase);

        $action = config('constants.concardis.ACTION');

        // create transaction entry in database
        $data = [
            'invoice_id'=> $invoice->id,
            'order_id'=> $order_id,
            'fields_sent'=> $fields,
            'status'=> 'P'
        ];

        // create concardis transaction
        ConcardisTransaction::create($data);

        return $this->sendResponse(true, 'Signing successful.', [
            'fields' => $fields,
            'action' => $action
        ]);
    }

    /* checkout function for test purpose */
    public function checkout()
    {
        // static
        $data['PSPID']    = config('constants.concardis.PSPID');
        $data['LANGUAGE'] = config('constants.concardis.LANGUAGE');
        $data['CURRENCY'] = config('constants.concardis.CURRENCY');

        $data['ORDERID']  = "ORDERID-test-30";

        // on the basis of booking invoice
        $data['CN']       = "Krunal - Satvara";
        $data['EMAIL']    = "krunals@zignuts.com";
        $data['AMOUNT']   = "10000";

        // urls
        $data['ACCEPTURL']  = route('concardis.accept');
        $data['DECLINEURL']  = route('concardis.decline');
        $data['EXCEPTIONURL']  = route('concardis.exception');
        $data['CANCELURL']  = route('concardis.cancel');

        // sha in passphrase
        $passphrase = config('constants.concardis.SHA_IN_PASSPHRASE');

        $data['SHASIGN'] = $this->generateSHA($data, $passphrase);

        $data['ACTION'] = config('constants.concardis.ACTION');

        return view('concardis.checkout')->with($data);
    }

    public function accept(Request $request)
    {
        Log::info('CONCARDIS_ACCEPT request:', ['params'=> $request->all(), 'full-url'=>$request->fullUrl()]);
        try {
            $acceptResult = $this->handleConcardisRequest($request);
        } catch (\Exception $e) {
            Log::info('CONCARDIS_ACCEPT error : '.$e->getMessage(), $request->only('orderID'));
            return redirect(config('constants.crm_error_page'));
        }

        Log::info('CONCARDIS_ACCEPT success', $request->only('orderID'));
        return redirect(config('constants.crm_payment_success_page'));
    }

    public function cancel(Request $request)
    {
        Log::info('CONCARDIS_CANCEL request:', ['params'=> $request->all(), 'full-url'=>$request->fullUrl()]);
        try {
            $cancelresult = $this->handleConcardisRequest($request);
        } catch (\Exception $e) {
            Log::info('CONCARDIS_CANCEL error : '.$e->getMessage(), $request->only('orderID'));
            return redirect(config('constants.crm_error_page'));
        }

        Log::info('CONCARDIS_CANCEL success', $request->only('orderID'));
        return redirect(config('constants.crm_payment_fail_page'));
    }

    public function decline(Request $request)
    {
        Log::info('CONCARDIS_DECLINE request:', ['params'=> $request->all(), 'full-url'=>$request->fullUrl()]);
        try {
            $declineresult = $this->handleConcardisRequest($request);
        } catch (\Exception $e) {
            Log::info('CONCARDIS_DECLINE error : '.$e->getMessage(), $request->only('orderID'));
            return redirect(config('constants.crm_error_page'));
        }

        Log::info('CONCARDIS_DECLINE success', $request->only('orderID'));
        return redirect(config('constants.crm_payment_fail_page'));
    }

    public function exception(Request $request)
    {
        Log::info('CONCARDIS_EXCEPTION request:', ['params'=> $request->all(), 'full-url'=>$request->fullUrl()]);
        try {
            $exceptionresult = $this->handleConcardisRequest($request);
        } catch (\Exception $e) {
            Log::info('CONCARDIS_EXCEPTION error : '.$e->getMessage(), $request->only('orderID'));
            return redirect(config('constants.crm_error_page'));
        }

        Log::info('CONCARDIS_EXCEPTION success', $request->only('orderID'));
        return redirect(config('constants.crm_payment_fail_page'));
    }

    /* helper functions for this controller */
    public function generateSHA($fields, $passphrase)
    {
        // change all keys to uppercase
        $fields = array_change_key_case($fields, CASE_UPPER);

        // sort array alphabetically by key
        ksort($fields);

        // string for sha
        $string = "";
        foreach ($fields as $key => $value) {
            // take only those values that are not null
            if ($value !== null) {
                $string .= $key . "=" . $value . $passphrase;
            }
        }

        return strtoupper(hash('sha1', $string));
    }

    public function handleConcardisRequest($request)
    {
        // sha out passphrase of test environment
        $passphrase = config('constants.concardis.SHA_OUT_PASSPHRASE');

        // generate sha out
        $shaout = $this->generateSHA($request->except('SHASIGN'), $passphrase);

        if ($request->get('SHASIGN') !== $shaout) {
            Log::info('CONCARDIS_SHA_SIGNING_FAILED', $request->only('orderID'));
            throw new \Exception('Sha signing failed.');
        }

        Log::info('CONCARDIS_SHA_SIGNING_PASSED', $request->only('orderID'));

        $transaction = ConcardisTransaction::where('order_id', $request->get('orderID'))->first();

        if (!$transaction) {
            Log::info('CONCARDIS_TRANSACTION_NOT_FOUND', $request->only('orderID'));
            throw new \Exception('Sha signing failed.');
        }

        if ($transaction->status !== 'P') {
            Log::info('CONCARDIS_TRANSACTION_ALREADY_RETURNED', $request->only('orderID'));
            throw new \Exception('Sha signing failed.');
        }

        $status = 'F';
        $payment_id = null;
        if ($request->get('STATUS') == '5') {

            // concardis payment method
            $paymentMethod = PaymentMethod::where('type', 'CON')->first();

            //Get office id for create payment
            $office = Office::where('is_head_office', '1')->first();
            if($office)
            {
                $office_id=$office->id;
            }

            // create payment
            $payment_details = [
                "contact_id"                => $transaction->invoice->payi_id,
                "payment_type"              => $paymentMethod->id,
                "is_office"                 => false,
                "office_id"                 => $office_id,
                "amount_given_by_customer"  => $request->amount,
                "amount_returned"           => 0,
                "total_amount"              => $transaction->invoice->total_price,
                "total_discount"            => $transaction->invoice->discount ?? 0,
                "total_vat_amount"          => $transaction->invoice->vat_amount,
                "total_net_amount"          => $request->amount,
                "total_lunch_amount"        => $transaction->invoice->include_lunch_price ?? 0,
                "total_lunch_vat_amount"    => $transaction->invoice->lunch_vat_amount ?? 0,
                "payment_card_type"         => $request->PM ?? '',
                "payment_card_brand"        => $request->BRAND ?? '',
            ];

            DB::beginTransaction();
            try {
                $payment = $this->createPayment($payment_details, [['id'=>$transaction->invoice_id]]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                /**For save exception */
                $this->saveException($e, $request->header(), $request->ip());
            }

            $status = 'S';
            $payment_id = $payment->id;
        }

        // update transaction
        $transaction->update([
            'fields_received' => $request->all(),
            'status' => $status,
            'payment_id' => $payment_id
        ]);

        return $request->all();
    }
}
