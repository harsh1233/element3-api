<?php

namespace App\Models;

use App\Http\Controllers\Functions;
use Illuminate\Database\Eloquent\Model;

class AccountMaster extends Model
{
    use Functions;

    protected $fillable = [
        'is_main',
        'contact_id',
        'office_id',
        'account_balance',
        'created_by',
        'updated_by'
    ];

    /* relations */
    public function details()
    {
        return $this->hasMany(AccountDetail::class, 'account_id');
    }

    /* helper functions */
    public function updateBalance($amount)
    {
        $this->account_balance += $amount;
        $this->save();
        return $this;
    }

    public function type()
    {
        return $this->contact_id ? 'user' : 'office';
    }

    public function getOpeningStatement()
    {
        return $this->details->where('event_code', 'OB')->first();
    }

    public function updateOpeningBalance($new_opening_balance, $old_opening_balance = 0)
    {
        $account_id = $this->id;
        $difference_in_balance = $new_opening_balance - $old_opening_balance;

        // update account balance
        $this->updateBalance($difference_in_balance);

        $event_code = 'OB';
        $event_id = null;
        $event_date = date('Y-m-d H:i:s');
        $opening_statement = $this->getOpeningStatement();
        $opening_statement_id = $opening_statement ? $opening_statement->id : null;

        // update/create opening balance entry
        $this->updateAccountDetail($account_id, $event_code, $event_id, $event_date, $new_opening_balance, $opening_statement_id);
    }

    public static function getElement3Account()
    {
        $account = AccountMaster::query();
        $account = $account->where('is_main', true)->first();

        if ($account) {
            return $account;
        }

        return AccountMaster::create(['is_main' => true]);
    }

    /* boot methods */
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
