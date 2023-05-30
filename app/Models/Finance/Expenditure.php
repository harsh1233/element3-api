<?php

namespace App\Models\Finance;

use App\User;
use App\Models\Finance\ExpenditureDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expenditure extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'is_product',
        'is_service',
        'description',
        'check_number',
        'reference_number',
        'date_of_expense',
        'receipt_images',
        'amount',
        'payment_type',
        'payment_status',
        'tax_consultation_status',
        'tax_consultation_done_by',
        'created_by',
        'updated_by'
    ];

    protected $dates = ['deleted_at'];

    protected $casts = [
        'receipt_images' => 'json'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function details()
    {
        return $this->hasMany(ExpenditureDetail::class);
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
