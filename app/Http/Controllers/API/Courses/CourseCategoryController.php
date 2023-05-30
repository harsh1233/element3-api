<?php

namespace App\Http\Controllers\API\Courses;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Courses\CourseCategory;

class CourseCategoryController extends Controller
{
    use Functions;

     /* List all course categories */
     public function listCategory(Request $request)
     {   
        $v = validator($request->all(), [
            'active_only' => 'boolean',
        ]);
        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $categories = CourseCategory::query();
        if($request->active_only) {
            $categories = $categories->where('is_active',true);
        }
        $categories = $categories->get();
        return $this->sendResponse(true,'success',$categories);
     }

     /* View course category detail */
     public function viewCategory($id)
     {   
         $category = CourseCategory::find($id);
         if(!$category) return $this->sendResponse(false,'Category not found');
         return $this->sendResponse(true,'success',$category);
     }

     /* Create new course category */
     public function createCategory(Request $request)
     {   
         $v = validator($request->all(), [
             'name' => 'required|max:50',
             'name_en' => 'nullable|max:50',
             'type' => 'required|in:HS,OS',
         ]);
         if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
         
         $input = $request->all();   
         $input['created_by'] = auth()->user()->id;
         CourseCategory::create($input);
         return $this->sendResponse(true,__('strings.course_category_create_success'));
     }

     /* Update existing course category */
     public function updateCategory(Request $request, $id)
     {   
         $v = validator($request->all(), [
             'name' => 'required|max:50',
             'name_en' => 'nullable|max:50',
             'type' => 'required|in:HS,OS',
         ]);
         if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
         
         $category = CourseCategory::find($id);
         if(!$category) return $this->sendResponse(false,'Category not found');
         
         $input = $request->all();   
         $input['updated_by'] = auth()->user()->id;
         $category->update($input);
         return $this->sendResponse(true,__('strings.course_category_update_success'));
     }

     /* Change status of course category */
     public function changeStatus(Request $request, $id)
     {   
         $v = validator($request->all(), [
             'status' => 'boolean',
         ]);
         if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
         
         $category = CourseCategory::find($id);
         if(!$category) return $this->sendResponse(false,'Category not found');

         $category->is_active = $request->status;
         $category->save();
         return $this->sendResponse(true,__('strings.status_change_success'));
     }
}
