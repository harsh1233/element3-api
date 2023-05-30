<?php

namespace App\Http\Controllers\API;
use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Exports\TeachingMaterialExport;
use App\Models\TeachingMaterial\TeachingMaterial;
use App\Models\TeachingMaterial\TeachingMaterialCategory;

class TeachingMaterialController extends Controller
{
    use Functions;

    /* List all teaching material */
    public function listTeachingMaterial(Request $request)
    { 
       /* $v = validator($request->all(), [
           'active_only' => 'boolean',
       ]);
       if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
       $teaching_material = TeachingMaterial::query();
       if($request->active_only) {
           $teaching_material = $teaching_material->where('is_active',true);
       }
       $teaching_material = $teaching_material->get();
       return $this->sendResponse(true,'success',$teaching_material); */
       $groups = TeachingMaterial::with('teaching_material_category_detail')
       ->with('teaching_material_sub_category_detail');
       
      /*  if($request->is_export){
           return Excel::download(new BookingProcessExport($groups->toArray()), 'TeachingMaterial.csv');
       } */
       //Server Side Pagination if Send PerPage & Page
       if($request->perPage)
       {
           $perPage=$request->perPage;
       }
       else
       {
           $perPage=20;
       }
       if($request->page)
       {
           $page=$request->page;
       }
       else
       {
           $page=1;
       }
       if($request->page && $request->perPage)
       {   
         $groupCount = $groups->count();
         $groups->skip($perPage*($page-1))->take($perPage);  
       }
       $groups = $groups->orderBy('display_order', 'asc')->get();
       $data=array();
       if($request->page && $request->perPage)
       {
           $data = [
               'groupes' => $groups,
               'count' => $groupCount
           ];
       }
       else
       {
           $data=$groups;
       }
       if(!empty($_GET['is_export'])){
           return Excel::download(new TeachingMaterialExport($groups->toArray()), 'TeachingMaterial.csv');  
       }
       return $this->sendResponse(true,'success',$data);
    }

      /* List all teaching material whos teaching material category parent id is zero */
     public function listTeachingMaterialWithParentZero(Request $request)
     {
       if($request->parent_id){
           $teaching_materials = TeachingMaterialCategory::where('parent_id', $request->parent_id)
           ->where('is_active', 1)
           ->get();
       }else{
           $teaching_materials = TeachingMaterialCategory::where('parent_id', 0)
           ->where('is_active', 1)
           ->get();
       }
        
        return $this->sendResponse(true,'success',$teaching_materials);
     }

     /* View Teaching material detail by ID*/
    public function viewTeachingMaterial($id)
    {   
       $teachingMaterial = TeachingMaterial::with(['teaching_material_category_detail'=>function($query){
           $query->get();
       }])
       ->find($id);
       if (!$teachingMaterial) return $this->sendResponse(false,'Teaching Material not found');
       return $this->sendResponse(true,'success',$teachingMaterial);
    }

     /* Create new teaching material */
     public function createTeachingMaterial(Request $request)
     {   
         $v = validator($request->all(), [
             'name' => 'required|max:50',
             'teaching_material_category_id' => 'required|integer|min:1',
             'teaching_material_sub_category_id' => 'required|integer|min:1',
             'formate' => 'in:Video,Audio,Pdf,Photo',
             'display_order' => 'integer|min:1',
         ]);
         if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
         
            $input = $request->all(); 

         if($request->formate == 'Video'){
                $input['url'] = $request->file;
             }
             else{
                $url = $this->uploadFile('contacts',$request->name,$request->file,$request->formate);
                //$url = $this->uploadImage('contacts',$request->name,$request->file);
                $input['url'] = $url; 
            }

         $input['created_by'] = auth()->user()->id;
         
         $TeachingMaterial = TeachingMaterial::create($input);

         /**Add crm user action trail */
        if ($TeachingMaterial) {
            $action_id = $TeachingMaterial->id; //teaching material id
            $action_type = 'A'; //A = Add
            $module_id = 15; //module id base module table
            $module_name = "Teaching Materials"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

         return $this->sendResponse(true,__('strings.teaching_material_create_success'));
     }

     /* Update existing teaching material */
     public function updateTeachingMaterial(Request $request, $id)
     {   
         $v = validator($request->all(), [
             'name' => 'required|max:50',
             'teaching_material_category_id' => 'required|integer|min:1',
             'teaching_material_sub_category_id' => 'required|integer|min:1',
             'formate' => 'in:Video,Audio,Pdf,Photo',
             'display_order' => 'integer|min:1',
         ]);
         if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
         
         $teaching_material = TeachingMaterial::find($id);
         if(!$teaching_material) return $this->sendResponse(false,'Teaching Material not found');
        
         $input = $request->all(); 
         if($request->formate == 'Video'){
                $input['url'] = $request->file;
         } else {
             if($request->formate && $request->imageUpdate) {
                 $url = $this->uploadFile('contacts',$request->name,$request->file,$request->formate);
                //$url = $this->uploadImage('contacts',$request->name,$request->file);
                $input['url'] = $url;
             }
        }   
         
         $input['updated_by'] = auth()->user()->id;
         $teaching_material->update($input);
         
         /**Add crm user action trail */
        if ($teaching_material) {
            $action_id = $teaching_material->id; //teaching material id
            $action_type = 'U'; //U = Updated
            $module_id = 15; //module id base module table
            $module_name = "Teaching Materials"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

         return $this->sendResponse(true,__('strings.teaching_material_update_success'));
     }

     /* Delete teaching material */
    public function deleteTeachingMaterial($id)
    {
        $teaching_material = TeachingMaterial::find($id);
        if (!$teaching_material) return $this->sendResponse(false,'Teaching material not found');
        TeachingMaterial::where('id',$id)->delete();

        /**Add crm user action trail */
        if ($teaching_material) {
            $action_id = $teaching_material->id; //teaching material id
            $action_type = 'D'; //D = Deleted
            $module_id = 15; //module id base module table
            $module_name = "Teaching Materials"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        $teaching_material->delete();
        return $this->sendResponse(true,__('strings.teaching_material_delete_success'));
    }

    /* Change status of teaching material*/
    public function changeStatus(Request $request, $id)
    {   
        $v = validator($request->all(), [
            'status' => 'boolean',
        ]);
        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        
        $teaching_material = TeachingMaterial::find($id);
        if(!$teaching_material) return $this->sendResponse(false,'Teaching material not found');

        $teaching_material->is_active = $request->status;
        $teaching_material->save();

        /**Add crm user action trail */
        if ($teaching_material) {
            $action_id = $teaching_material->id; //teaching material id
            
            if ($request->status) {
                $action_type = 'ACS';
            } //ACS = Active Change Status
            else {
                $action_type = 'DCS';
            } //DCS = Deactive Change Status

            $module_id = 15; //module id base module table
            $module_name = "Teaching Materials"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        return $this->sendResponse(true,__('strings.status_change_success'));
    }
}
