<?php

namespace App\Models\Permissions;

use App\Models\Permissions\Privilege;
use Illuminate\Database\Eloquent\Model;

class RolePrivilege extends Model
{
	public $timestamps = false;
    protected $table = 'e3_role_privilege_map'; 	
	protected $fillable = ['role_id','privilage_id'];

	public function privilage()
	{
		$this->hasOne(Privilege::class,'role_id','id');
	}

	public function rolePrivialges()
	{
		
	return $this->hasOne(Privilege::class,'id','privilage_id');	
		
	}
}
