<?php

namespace App\Models\SubChild;

use App\Models\Allergy;
use App\Models\Language;
use Illuminate\Database\Eloquent\Model;

class SubChildContactAllergy extends Model
{
    public $timestamps = false;
    protected $fillable = ['allergy_id','sub_childe_contact_id'];

    public function allergy()
    {
        return $this->belongsTo(Allergy::class,'allergy_id');
    }
}
