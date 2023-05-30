<?php

namespace App\Models;

use App\Models\Contact;
use App\Models\Leave;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactLeave extends Model
{
    use SoftDeletes;

    protected $fillable = ['contact_id','leave_id','start_date','end_date','no_of_days','reason','leave_status','is_paid','created_by','updated_by','reject_reason','description'];
    
    public function contact_detail()
    {
        return $this->belongsTo(Contact::class,'contact_id');
    }

    public function leave_detail()
    {
        return $this->belongsTo(Leave::class,'leave_id');
    }


}
