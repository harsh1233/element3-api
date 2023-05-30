<?php

namespace App\Models\Finance;

use App\Models\BookingProcess\BookingProcessPaymentDetails;
use App\Models\Contact;
use App\Models\Courses\Course;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Voucher extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'status',
        'contact_id',
        'contact_name',
        'customer_id',
        'customer_name',
        'name',
        'course_id',
        'date_of_purchase',
        'amount_type',
        'amount',
        'created_by',
        'updated_by',
        'max_number_times_use'
    ];

    protected $dates = ['deleted_at'];

    protected $appends = ['invoices_cost', 'max_number_times_applied'];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }


    public function customer()
    {
        return $this->belongsTo(Contact::class, 'customer_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function invoice()
    {
        return $this->hasOne(BookingProcessPaymentDetails::class);
    }

    public static function boot()
    {
        parent::boot();

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

    public function apply($invoice_id)
    {
        // voucher is already used
        if ($this->status === 'U') {
            throw new \Exception('Voucher is expired.');
        }

        $invoice = BookingProcessPaymentDetails::find($invoice_id);
        if (!$invoice) {
            throw new \Exception('Invoice not found.');
        }

        //if voucher has contact && voucher has different contact than the booking invoice
        if ($this->contact_id) {
            if ($this->contact_id !== $invoice->payi_id) {
                throw new \Exception('Voucher code cannot be used for this user.');
            }
        }

        // voucher has different course than the booking invoice
        if ($this->course_id !== $invoice->course_detail->course_id) {
            throw new \Exception('Voucher code cannot be used for this course.');
        }

        // apply voucher to invoice.
        $invoice->update([
            'voucher_id' => $this->id
        ]);
        $invoice->load('voucher');

        // Update voucher as used if voucher max number times use.
        $applied_count = BookingProcessPaymentDetails::where('voucher_id', $this->id)->count();
        if ($this->max_number_times_use === $applied_count) {
            $this->update([
                'status' => 'U'
            ]);
        }

        return $invoice;
    }

    /**Get voucher applied count */
    public function getMaxNumberTimesAppliedAttribute()
    {
        return BookingProcessPaymentDetails::where('voucher_id', $this->id)->count();
    }

    /**Get voucher applied amount */
    public function getInvoicesCostAttribute()
    {
        $invoices = BookingProcessPaymentDetails::where('voucher_id', $this->id)->get();
        $total_applied_cost = 0;
        $voucher = Self::find($this->id);
        if($voucher){
            foreach($invoices as $in){
                if($voucher->amount_type === 'P'){
                    $voucher_amount = ($in->total_price * $voucher->amount) / 100;
                    $total_applied_cost = $total_applied_cost + $voucher_amount;
                }else{
                    $total_applied_cost = $total_applied_cost + $voucher->amount;
                }
            }
        }
        return $total_applied_cost;
    }
}
