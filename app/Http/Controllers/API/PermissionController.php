<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\Permissions\Menu;
use App\Models\Permissions\Role;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Permissions\Privilege;
use App\Models\Permissions\PrivilegeMenu;
use App\Models\Permissions\RolePrivilege;

class PermissionController extends Controller
{
    use Functions;
    
    /* Get all menu list */
    public function getAllMenus()
    {
        $menus=Menu::where('parent_id',0)->with('submenu')->orderBy('display_order', 'asc')->get();
        return $this->sendResponse(true,'success',$menus);
    }

    /* Get user authenticated menu */
    public function getUserMenus($role)
    {
        $menus = $this->getMenus($role);   
        return $this->sendResponse(true,'success',$menus);
    }

    /* Create new privilege */
    public function createPrivilege(Request $request)
    {
        $v = validator($request->all(), [
            'name' =>'required',
            'description'=>'required',
            'permissionArray'=>'required'
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        $input = [];
        $input['name']=$request->name;
        $input['description']=$request->description;
        $privilege = Privilege::create($input);
        $insertedId = $privilege->id;

		$permissionArray = json_decode($request->permissionArray, true);			
        $assignPrivilage=[];
        
        for($row=0;$row<sizeof($permissionArray);$row++) {			
            $assignPrivilage['privilege_id']=$insertedId;
            $assignPrivilage['menu_id']=$permissionArray[$row]['menuid'];
            $assignPrivilage['premissionArray']=json_encode(array('add' => $permissionArray[$row]['add'],'edit'=>$permissionArray[$row]['edit'],'del'=>$permissionArray[$row]['del']));
            PrivilegeMenu::create($assignPrivilage);
        }
        
        return $this->sendResponse(true,__('Privilege created successfully'));
    }

   /*  Update privilege */
    public function updatePrivilege(Request $request, $id)
    {
        $v = validator($request->all(), [
            'name' =>'required',
            'description'=>'required',
            'permissionArray'=>'required'
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        $privilege = Privilege::find($id);
        if (!$privilege) return $this->sendResponse(false,'Privilege not found');
        
        $privilege->name = $request->name;
        $privilege->description = $request->description;
        $privilege->save();
        
        $permissionArray = json_decode($request->permissionArray, true);			
        $assignPrivilage=[];
        PrivilegeMenu::where('privilege_id',$id)->delete();
        for($row=0;$row<sizeof($permissionArray);$row++) {			
            $assignPrivilage['privilege_id']=$id;
            $assignPrivilage['menu_id']=$permissionArray[$row]['menuid'];
            $assignPrivilage['premissionArray']=json_encode(array('add' => $permissionArray[$row]['add'],'edit'=>$permissionArray[$row]['edit'],'del'=>$permissionArray[$row]['del']));
            PrivilegeMenu::create($assignPrivilage);
        }
        
        return $this->sendResponse(true,__('Privilege update successfully'));
    }

    /* Delete privilege */
    public function deletePrivilege($id)
    {
        $privilege = Privilege::find($id);
        if (!$privilege) return $this->sendResponse(false,'Privilege not found');
        PrivilegeMenu::where('privilege_id',$id)->delete();
        $privilege->delete();    
        return $this->sendResponse(true,__('Privilege deleted successfully'));
    }

    /* Get all privilege list */
    public function getAllPrivileges() 
    {
        $privileges = Privilege::latest()->get();
        return $this->sendResponse(true,'success',$privileges);
    }

    /* Get privilege detail */
    public function viewPrivilege($id) 
    {
        $privilege = Privilege::with('privilage_menu_maps')->find($id);
        if (!$privilege) return $this->sendResponse(false,'Privilege not found');
        return $this->sendResponse(true,'success',$privilege);
    }


    /* Create new role */
    public function createRole(Request $request)
    {
        $v = validator($request->all(), [
            'name' =>'required',
            'description'=>'required',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $input = [];
        $input['name']=$request->name;
        $input['description']=$request->description;
        $role = Role::create($input);
        $insertedId = $role->id;

        $privileges=$request->privilage_ids;
        if($privileges) {
            for($row=0;$row<sizeof($privileges);$row++){
                $assignPrivilage = [];					
                $assignPrivilage['role_id']=$insertedId;
                $assignPrivilage['privilage_id']=$privileges[$row];		        
                $result = RolePrivilege::create($assignPrivilage);	    	
            }	
        }
        return $this->sendResponse(true,__('Role created successfully'));
    }

    /* Update existing role */
    public function updateRole(Request $request,$id)
    {
        $v = validator($request->all(), [
            'name' =>'required',
            'description'=>'required',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $role = Role::find($id);
        if (!$role) return $this->sendResponse(false,'Role not found');
        
        $role->name = $request->name;
        $role->description = $request->description;
        $role->save();

        $privileges=$request->privilage_ids;
        if($privileges) {
            RolePrivilege::where('role_id',$id)->delete();
            for($row=0;$row<sizeof($privileges);$row++){
                $assignPrivilage = [];					
                $assignPrivilage['role_id']=$id;
                $assignPrivilage['privilage_id']=$privileges[$row];		        
                $result = RolePrivilege::create($assignPrivilage);	    	
            }	
        }
        return $this->sendResponse(true,__('Role updated successfully'));
    }

    /* Delete role */
    public function deleteRole($id)
    {
        $role = Role::find($id);
        if (!$role) return $this->sendResponse(false,'Role not found');
        RolePrivilege::where('privilage_id',$id)->delete();
        $role->delete();
        return $this->sendResponse(true,__('Role deleted successfully'));
    }

    /* Get all role list */
    public function getAllRoles() 
    {
        $roles = Role::where('is_active',true)->latest()->get();
        return $this->sendResponse(true,'success',$roles);
    }

    /* Get role detail */
    public function viewRole($id) 
    {
        $role = Role::with('role_privilage_maps')->find($id);
        if (!$role) return $this->sendResponse(false,'Role not found');
        return $this->sendResponse(true,'success',$role);
    }

    /**Check URL is accessable or not for this Logged in admin */
    public function checkRoleBaseUrl(Request $request)
    {
        $v = validator($request->all(), [
            'module_name' =>'required',
            'type'=>'required'
        ]);
        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        /**For check role, module, and permissions base enter URL is valid or not */
        $module_data = $this->getRoleBaseUrl($request->all());
        
        $msg = $module_data['msg'];
        $data = [
            "module" => $module_data['module'],
            "valid" => $module_data['valid']
        ];
        return $this->sendResponse(true, $msg, $data);
    }
}
