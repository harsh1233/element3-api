<?php

namespace App\Models;

use App\User;
use App\Models\Modules;
use App\Models\SalaryGroup;
use App\Models\Finance\Cash;
use App\Models\Courses\Course;
use App\Models\Finance\Voucher;
use App\Models\TeachingMaterial\TeachingMaterial;
use App\Models\Finance\Expenditure;
use Illuminate\Database\Eloquent\Model;
use App\Models\BookingProcess\BookingEstimate;
use App\Models\BookingProcess\BookingProcesses;
use App\Models\BookingProcess\BookingProcessPaymentDetails;
use App\Models\BookingProcess\ConsolidatedInvoice;

class CrmUserActionTrail extends Model
{
    protected $fillable = ['module_id','module_name','action_id','action_type','created_by','created_at'];
    
    public function module_detail()
    {
        return $this->belongsTo(Modules::class, 'module_id');
    }

    public function user_detail()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function action_details()
    {
        if ($this->module_name  === 'Voucher') {
            return $this->belongsTo(Voucher::class, 'action_id')->withTrashed();
        } elseif ($this->module_name  === 'Users') {
            return $this->belongsTo(User::class, 'action_id')->withTrashed();
        } elseif ($this->module_name  === 'Payroll') {
            return $this->belongsTo(Payroll::class, 'action_id')->withTrashed();
        } elseif ($this->module_name  === 'Contacts') {
            return $this->belongsTo(Contact::class, 'action_id')->withTrashed();
        }elseif ($this->module_name  === 'All Leaves') {
            return $this->belongsTo(ContactLeave::class, 'action_id')->withTrashed();
        }elseif ($this->module_name  === 'Bookings') {
            return $this->belongsTo(BookingProcesses::class, 'action_id')->withTrashed();
        }elseif ($this->module_name  === 'Expenditure') {
            return $this->belongsTo(Expenditure::class, 'action_id')->withTrashed();
        }elseif ($this->module_name  === 'Cash Payment') {
            return $this->belongsTo(Cash::class, 'action_id')->withTrashed();
        }elseif ( ($this->module_name  === 'Payments') || ($this->module_name  === 'Customer Invoices')) {
            return $this->belongsTo(BookingProcessPaymentDetails::class, 'action_id')->withTrashed();
        }elseif ($this->module_name  === 'Booking Estimate'){
            return $this->belongsTo(BookingEstimate::class, 'action_id')->withTrashed();
        }elseif ($this->module_name  === 'Teaching Materials'){
            return $this->belongsTo(TeachingMaterial::class, 'action_id');
        }elseif ($this->module_name  === 'Course Catalog'){
            return $this->belongsTo(Course::class, 'action_id');
        }elseif ($this->module_name  === 'Salary Groups'){
            return $this->belongsTo(SalaryGroup::class, 'action_id')->withTrashed();
        }elseif ($this->module_name  === 'Season Ticket'){
            return $this->belongsTo(SeasonTicketManagement::class, 'action_id')->withTrashed();
        }elseif ($this->module_name  === 'Instructor block'){
            return $this->belongsTo(InstructorBlock::class, 'action_id')->withTrashed();
        }elseif ($this->module_name  === 'Consolidated Invoices'){
            return $this->belongsTo(ConsolidatedInvoice::class, 'action_id')->withTrashed();
        }
        else {
            dd($this->module_name);
        }
    }
}
