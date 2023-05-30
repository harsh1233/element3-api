<?php

namespace App\Models;

use App\Models\AccountMaster;
use App\Models\Country;
use App\Models\Finance\Cash;
use Illuminate\Database\Eloquent\Model;

class Office extends Model
{

    protected $appends = ['current_balance'];

    protected $fillable = [
        'name',
        'street_address1',
        'street_address2',
        'city',
        'state',
        'country',
        'opening_balance',
        'day_start_sum',
        'is_head_office',
        'created_by',
        'updated_by'
    ];

    // Accessor
    public function getCurrentBalanceAttribute()
    {
        $last_entry=$this->cashEntries()->orderBy('date_of_entry', 'desc')->orderBy('id','DESC')->first();
        return $last_entry ? $last_entry->running_amount : 0 ;
    }
    public function cashEntries()
    {
        return $this->hasMany(Cash::class);
    }

    public function country_detail()
    {
        return $this->belongsTo(Country::class, 'country');
    }

    public function account()
    {
        return $this->hasOne(AccountMaster::class, 'office_id');
    }

    public function getAccount()
    {
        if ($this->account) {
            return $this->account;
        }

        return AccountMaster::create([
            'office_id' => $this->id,
        ]);
    }
}
