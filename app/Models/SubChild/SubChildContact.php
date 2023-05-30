<?php

namespace App\Models\SubChild;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\SubChild\SubChildContactAllergy;
use App\Models\SubChild\SubChildContactLanguage;

class SubChildContact extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'contact_id',
        'first_name',
        'last_name',
        'email',
        'mobile1',
        'mobile2',
        'address',
        'zip',
        'dob',
        'city',
        'country',
        'accomodation',
        'skiing_level',
        'gender',
        'accommodation_id'
    ];

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

    public function contact()
    {
        return $this->belongsTo(Contact::class,'contact_id');
    }

    public function allergies()
    {
        return $this->hasMany(SubChildContactAllergy::class, 'sub_childe_contact_id');
    }

    public function languages()
    {
        return $this->hasMany(SubChildContactLanguage::class, 'sub_childe_contact_id');
    }

    public function accommodation_data()
    {
        return $this->belongsTo(Contact::class,'accommodation_id');
    }
}
