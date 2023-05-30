<?php

namespace App\Models\InstructorActivity;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BookingProcess\BookingProcesses;

class InstructorActivityComment extends Model
{
    use SoftDeletes;

    protected $fillable = ['booking_id','comment_by','comment_user_id','description','comment_date','created_by','updated_by'];

    public function booking_detail()
    {
        return $this->belongsTo(BookingProcesses::class,'booking_id');
    }

    public function comment_user_detail()
    {
        return $this->belongsTo(User::class,'comment_user_id');
    }
}
