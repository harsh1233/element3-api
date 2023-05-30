<?php

namespace App\Models\Permissions;

use Illuminate\Database\Eloquent\Model;
use App\Models\Permissions\PrivilegeMenu;

class Menu extends Model
{
    protected $table = 'e3_menus'; 	

	public function parent() {
    return $this->belongsToOne(static::class, 'parent_id');
  	}

   //each category might have multiple children
   public function subMenu() {
    return $this->hasMany(static::class, 'parent_id')->orderBy('display_order', 'asc');
   }

   public function privileges() {
    return $this->hasMany(PrivilegeMenu::class, 'menu_id');
   }
}
