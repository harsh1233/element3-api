<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use App\Models\TeachingMaterial\TeachingMaterialCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;

class TeachingMaterialCategoryController extends Controller
{
    use Functions;

    /* List all teaching categories */
     public function listTeachingCategory(Request $request)
     {   
        /* $v = validator($request->all(), [
            'active_only' => 'boolean',
        ]);
        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $teaching_category = TeachingMaterialCategory::query();
        if($request->active_only) {
            $teaching_category = $teaching_category->where('is_active',true);
        }
        $teaching_category = $teaching_category->get();
        return $this->sendResponse(true,'success',$teaching_category); */

        $groups = TeachingMaterialCategory::with('teaching_material_detail')
        ->with('parent_detail')
        ->orderByRaw('coalesce(id,parent_id)')
        ->get();

        //$groups=TeachingMaterialCategory::where('parent_id',0)->with('sub_category')->orderBy('id', 'asc')->get();

        return $this->sendResponse(true,'success',$groups);
        
     }

     /* Create new teaching category */
     public function createTeachingCategory(Request $request)
     {   
         $v = validator($request->all(), [
             'name' => 'required|max:50',
         ]);
         if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
         
         $input = $request->all();   
         $input['created_by'] = auth()->user()->id;
         TeachingMaterialCategory::create($input);
         return $this->sendResponse(true,__('strings.teaching_category_create_success'));
     }

     /* Update existing teaching category */
     public function updateTeachingCategory(Request $request, $id)
     {   
         $v = validator($request->all(), [
             'name' => 'required|max:50',
         ]);
         if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
         
         $teaching_category = TeachingMaterialCategory::find($id);
         if(!$teaching_category) return $this->sendResponse(false,'Teaching Category not found');
         
         $input = $request->all();   
         $input['updated_by'] = auth()->user()->id;
         $teaching_category->update($input);
         return $this->sendResponse(true,__('strings.teaching_category_update_success'));
     }

     /* Delete teaching category */
    public function deleteTeachingCategory($id)
    {
        $teaching_category = TeachingMaterialCategory::find($id);
        if (!$teaching_category) return $this->sendResponse(false,'Teaching Category not found');
        TeachingMaterialCategory::where('id',$id)->delete();
        $teaching_category->delete();
        return $this->sendResponse(true,__('strings.teaching_category_delete_success'));
    }

    /* Change status of teaching material category */
    public function changeStatus(Request $request, $id)
    {   
        $v = validator($request->all(), [
            'status' => 'boolean',
        ]);
        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        
        $teaching_category = TeachingMaterialCategory::find($id);
        if(!$teaching_category) return $this->sendResponse(false,'Teaching material category not found');

        $teaching_category->is_active = $request->status;
        $teaching_category->save();
        return $this->sendResponse(true,__('strings.status_change_success'));
    }
}
