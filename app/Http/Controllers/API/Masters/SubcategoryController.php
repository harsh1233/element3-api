<?php

namespace App\Http\Controllers\API\Masters;

use App\Models\Subcategory;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;

class SubcategoryController extends Controller
{
    use Functions;
    
    /** Get all categories */
    public function getCategories()
    {
        $categories = Category::with('subcategories')->get();
        return $this->sendResponse(true,'success',$categories);
    }

    /** Get all subcategories with parent category */
    public function getSubcategories()
    {
        $subcategories = Subcategory::with('category_detail')->orderBy('is_system','DESC')->orderBy('created_at','DESC')->get();
        return $this->sendResponse(true,'success',$subcategories);
    }

    /** Create new subcategory */
    public function createSubcategory(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required|max:50',
            'category_id' => 'required|integer|min:1',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        $input = $request->all();
        $input['created_by'] = auth()->user()->id;
        $subcategory = Subcategory::create($input);
        return $this->sendResponse(true,'success',$subcategory);
    }

    /** Update subcategory */
    public function updateSubcategory(Request $request,$id)
    {
        $v = validator($request->all(), [
            'name' => 'required|max:50',
            'category_id' => 'required|integer|min:1',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        $subcategory = Subcategory::find($id);
        if (!$subcategory) return $this->sendResponse(false,'Subcategory not found');
        $input = $request->all();
        $input['updated_by'] = auth()->user()->id;
        $subcategory->update($input);
        return $this->sendResponse(true,'success',$subcategory);
    }

    /** delete subcategory */
    public function deleteSubcategory($id)
    {
        $subcategory = Subcategory::find($id);
        if (!$subcategory) return $this->sendResponse(false,'Subcategory not found');
        $subcategory->delete();
        return $this->sendResponse(true,'success',$subcategory);
    }
}
