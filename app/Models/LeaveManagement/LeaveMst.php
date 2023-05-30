<?php

namespace App\Models\LeaveManagement;

use App\Models\BookingProcess\BookingProcesses;
use App\User;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveMst extends Model
{
    use SoftDeletes;

    protected $table = 'leave_management_mst';
    
    protected $fillable = ['user_id','subject','description','date','created_by','updated_by','status','booking_id'];

    public function user_detail()
    {
        return $this->belongsTo(User::class,'user_id');
    }
    public function contact()
    {
        return $this->belongsTo(Contact::class,'user_id');
    }
    public function booking_detail()
    {
        return $this->belongsTo(BookingProcesses::class,'booking_id');
    }
}
