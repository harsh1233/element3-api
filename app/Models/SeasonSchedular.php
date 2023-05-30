<?php

namespace App\Models;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SeasonSchedular extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'contact_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'description'
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
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
