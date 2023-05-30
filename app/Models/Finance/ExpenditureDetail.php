<?php

namespace App\Models\Finance;

use App\Models\Finance\Expenditure;
use Illuminate\Database\Eloquent\Model;

class ExpenditureDetail extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'expenditure_id',
        'action',
        'rejection_deletion_reason',
        'created_at',
        'created_by'
    ];

    public function expenditure()
    {
        return $this->belongsTo(Expenditure::class, 'user_id');
    }

    public static function boot()
    {
        parent::boot();

        // create a event to happen on creating
        static::creating(function ($record) {
            $record->created_at = date("Y-m-d H:i:s");
            $record->created_by = auth()->user()->id;
        });
    }
}
