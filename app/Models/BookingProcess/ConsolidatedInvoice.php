<?php

namespace App\Models\BookingProcess;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BookingProcess\ConsolidatedInvoiceProduct;
use App\Models\BookingProcess\BookingProcessPaymentDetails;

class ConsolidatedInvoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'total_amount',
        'grant_amount',
        'emails',
        'invoices',
        'vat_percentage',
        'vat_amount',
        'vat_excluded_amount',
        'payment_method_id',
        'settlement_amount',
        'settlement_description',
        'is_reverse_charge'
    ];

    /**Declare variables type */
    protected $casts = [
        'emails'  =>  'array',
        'invoices'  =>  'array'
    ];

    /**For run time append variable */
    protected $appends = ['invoice_details', 'payment_status'];

    public static function boot()
    {
        parent::boot();

        if(auth()->user()){
            // create a event to happen on creating
            static::creating(function ($record) {
                $record->created_at = date("Y-m-d H:i:s");
                $record->created_by = auth()->user()->id;
            });
    
            // create a event to happen on updating
            static::updating(function ($record) {
                $record->updated_at = date("Y-m-d H:i:s");
                $record->updated_by = auth()->user()->id;
            });
        }
    }

    /**Model call time get invoices ids base return invoice data */
    public function getInvoiceDetailsAttribute()
    {
        $invoices = $this->invoices;
        $invoices_data = array();
        if ($invoices) {
            foreach ($invoices as $invoice) {
                $invoices_data[] = BookingProcessPaymentDetails::find($invoice);
            }
        }
        return $invoices_data;
    }

    /**Model call time get invoices ids base return invoice data */
    public function getPaymentStatusAttribute()
    {
        $invoices = $this->invoices;
        $invoices_data = array();
        $payment_status = 'Success';
        $cancelled_count = 0;
        if ($invoices) {
            foreach ($invoices as $invoice) {
                $invoices_data = BookingProcessPaymentDetails::find($invoice);
                if($invoices_data){
                    if($invoices_data->status == 'Pending'){
                        $payment_status = 'Pending';
                    }else if($invoices_data->status == 'Cancelled'){
                        $cancelled_count = $cancelled_count + 1;
                    }
                }
            }
        }

        if($cancelled_count == count($invoices)){
            return 'Cancelled';
        }else{
            return $payment_status;
        }
    }

    /**Product details */
    public function product_detail()
    {
        return $this->hasMany(ConsolidatedInvoiceProduct::class,'consolidated_invoice_id');
    }
}
