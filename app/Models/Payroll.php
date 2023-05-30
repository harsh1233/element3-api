<?php

namespace App\Models;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payroll extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'month',
        'year',
        'total_days',
        'working_days',
        'total_contacts',
        'total_contacts_processed',
        'amount',
        'status',
        'created_by',
        'updated_by'
    ];

    public function payslips()
    {
        return $this->hasMany(Payslip::class);
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
