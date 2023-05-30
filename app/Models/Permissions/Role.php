<?php

namespace App\Models\Permissions;

use App\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\Permissions\RolePrivilege;

class Role extends Model
{
    protected $table = 'e3_roles'; 
	protected $fillable = [
        'name', 'description','is_active','code'
    ];

	//User-Role relationship
	public function user_detail()
	{
		return $this->hasMany(User::class,'role','id');
	}

	public function role_privilage_maps()
	{
		return $this->hasMany(RolePrivilege::class,'role_id','id');
	}
}
