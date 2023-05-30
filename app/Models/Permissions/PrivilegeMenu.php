<?php

namespace App\Models\Permissions;

use App\Models\Permissions\Menu;
use Illuminate\Database\Eloquent\Model;

class PrivilegeMenu extends Model
{
	public $timestamps = false;
    protected $table = 'e3_privilege_menu_map'; 
	protected $fillable = ['menu_id', 'premissionArray','privilege_id'];	

	public function menulist()
	{
		return $this->hasOne(Menu::class,'id','menu_id');		
	}
}
