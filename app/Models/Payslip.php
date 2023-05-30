<?php

namespace App\Models;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payslip extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payroll_id',
        'contact_id',
        'salary_name',
        'salary_type',
        'salary_amount',
        'salary_sum_per_extra_hour',
        'salary_paid_sick_leave',
        'salary_paid_vacation_leave',
        'total_leaves',
        'present_days',

        'leaves_pending',
        'leaves_approved',
        'leaves_approved',
        'leaves_rejected',
        'leaves_paid',
        'leaves_unpaid',
        'days_paid',
        'total_hours',
        'hours_pending',
        'hours_inprogress',

        'hours_approved',
        'hours_approved_overtime',
        'hours_rejected',
        'expenditure_debt',
        'settlement_amount',
        'settlement_description',
        'normal_payout',
        'overtime_payout',
        'total_payout',
        'check_number',

        'ref_number',
        'payment_type',
        'status',
        'comments',
        'approved_paid_block_hours',
        'paid_block_amount',

        'course_total_hours',
        'course_total_days'
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public static function boot()
    {
        parent::boot();

        // create a event to happen on creating
        static::creating(function ($record) {
            $record->created_at = date("Y-m-d H:i:s");
            $record->created_by = auth()->user() ? auth()->user()->id : '2';
        });

        // create a event to happen on updating
        static::updating(function ($record) {
            $record->updated_at = date("Y-m-d H:i:s");
            $record->updated_by = auth()->user() ? auth()->user()->id : '2';
        });
    }
}
