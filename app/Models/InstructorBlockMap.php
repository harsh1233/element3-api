<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Contact;
use App\Models\InstructorBlock;

class InstructorBlockMap extends Model
{
    /**Class for soft delete */
    use SoftDeletes;

    protected $fillable = ['instructor_id', 'instructor_block_id', 'start_date', 'end_date', 'start_time', 'end_time', 'created_by','is_release'];

    public function instructor_details()
    {
        return $this->belongsTo(Contact::class, 'instructor_id');
    }
    
    public function instructor_blocks()
    {
        return $this->belongsTo(InstructorBlock::class, 'instructor_block_id');
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
}
