<?php

namespace App\Models;

use App\Models\CustomerGroup;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['name','description','is_running','created_by','updated_by'];

    public function customers()
    {
        return $this->hasMany(CustomerGroup::class,'group_id');
    }
}
