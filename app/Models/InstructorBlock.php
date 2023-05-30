<?php

namespace App\Models;

use App\MeetingPoint;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstructorBlock extends Model
{
    /**Class for soft delete */
    use SoftDeletes;

    protected $fillable = ['instructor_id', 'title', 'start_date', 'end_date', 'start_time', 'end_time', 'description', 'block_color', 'amount', 'is_paid', 'meeting_point','meeting_point_other_name','block_label_id'];

    protected $appends = ['is_expired'];

    public function instructor_details()
    {
        return $this->belongsTo(Contact::class, 'instructor_id');
    }

    public function meeting_point_details()
    {
        return $this->belongsTo(MeetingPoint::class, 'meeting_point');
    }

    /**For record create or update time then update some fields */
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

    /**Check block expired or not */
    public function getIsExpiredAttribute()
    {
        $start_date_time = $this->start_date.' '.$this->start_time;
        $end_date_time = $this->end_date.' '.$this->end_time;
        $is_expired = false;

        if(date('Y-m-d H:i:s') > $start_date_time && date('Y-m-d H:i:s') > $end_date_time){
            $is_expired = true;
        }
        return $is_expired;
    }
}
