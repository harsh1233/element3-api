<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\EldaFunctions;
use App\Models\Contact;
use Illuminate\Database\Eloquent\SoftDeletes;

class EldaDetail extends Model
{
    use SoftDeletes;

    protected $fillable = ['function_id','comment','elda_insurance_number','elda_insurance_reference_number','is_requested_number','joining_date','employement_area','pension_contribution_from','is_free_service_contract','contact_id','status','date','minority'];

    /**Function details */
    public function function_detail(){
        return $this->belongsTo(EldaFunctions::class, 'function_id');
    }

    /**Contact details */
    public function contact_detail(){
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    /**While record create or udpate then auto timestemp value will update */
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
