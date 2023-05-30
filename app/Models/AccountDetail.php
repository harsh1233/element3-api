<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id',
        'event_code',
        'event_id',
        'event_date',
        'transaction_type',
        'transaction_desc',
        'amount',
        'running_amount',
        'created_by',
        'updated_by',
    ];

    protected $dates = ['deleted_at'];

    public static function boot()
    {
        parent::boot();

        // create a event to happen on creating
        static::creating(function ($record) {
            $record->created_by = auth()->user() ? auth()->user()->id : '2';
        });

        // create a event to happen on updating
        static::updating(function ($record) {
            $record->updated_by = auth()->user() ? auth()->user()->id : '2';
        });
    }
}
