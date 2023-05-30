<?php

namespace App\Models\Finance;

use App\Models\AccountDetail;
use App\Models\Contact;
use App\Models\Office;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cash extends Model
{
    use SoftDeletes;

    protected $table = 'cash_flow';

    protected $fillable = [
        'type', // CHKIN = Check in, CHKOUT = Check out at the day end, CASHOUT = Cash out, CASHIN = Cash In.
        'office_id',
        'contact_id', // required for CASHOUT and CASHIN
        'description',
        'date_of_entry',
        'amount',
        'running_amount',
        'created_by',
        'updated_by'
    ];

    protected $dates = ['deleted_at'];


    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function account_detail()
    {
        return AccountDetail::where('event_code', $this->type)->where('event_id', $this->id)->first();
    }

    public function created_by_user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updated_by_user()
    {
        return $this->belongsTo(User::class, 'updated_by');
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
