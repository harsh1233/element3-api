<?php

namespace App\Models\Permissions;

use Illuminate\Database\Eloquent\Model;
use App\Models\Permissions\PrivilegeMenu;

class Privilege extends Model
{
    protected $table = 'e3_privileges'; 	
	protected $fillable = ['name', 'description','is_active'];


	public function privilage_menu_maps()
	{
		return $this->hasMany(PrivilegeMenu::class,'privilege_id','id');
	}
}
