<?php

namespace App\Http\Controllers\API\Masters;

use App\Exports\LanguagesExport;
use App\Models\Language;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class LanguageController extends Controller
{
    use Functions;
    
    /** Get all Languages */
    public function getLanguages()
    {
        $languages = Language::orderBy('display_order','asc')->orderBy('name','asc')->get();
        return $this->sendResponse(true,'List of languages',$languages);
    }

    /** Get all Languages with paginations */
    public function getLanguagesWithPagination(Request $request)
    {
        /** If Export Data to csv request ignore validation */
        if(!$request->is_export)
        {

        $v = validator($request->all(), [
            'page' => 'required|integer|min:1',
            'perPage' => 'required|integer|min:1',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        }
        
        $page = $request->page;    
        $perPage = $request->perPage;    
        $languages = Language::select('id','name','display_order','created_at')->orderBy('display_order','asc')->orderBy('name','asc');
        $languageCount = $languages->count();
         /** If Export Data to csv request ignore pagination*/
        if(!$request->is_export)
        {
        $languages->skip($perPage*($page-1))->take($perPage);
        }

        if($request->search) {
            $search = $request->search;
            $languages = $languages->where(function($query) use($search){
                $query->where('name','like',"%$search%");
            });
            $languageCount = $languages->count();
        }
       
        $languages = $languages->latest()->get();

        /*** For Export Data To CSV  ***/
        if ($request->is_export) {
            return Excel::download(new LanguagesExport($languages->toArray()), 'Languages.csv');
        }
        /* End Export to CSV */

        $data = [
            'languages' => $languages,
            'count' => $languageCount
        ];
        return $this->sendResponse(true,'success',$data);
    }

    /** Create new Language */
    public function createLanguage(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required|max:50|unique:languages',
            'display_order' => 'required'
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $input = $request->all();
        $input['created_by'] = auth()->user()->id;
        $language = Language::create($input);
        return $this->sendResponse(true,'success',$language);
    }

    /** Update Language */
    public function updateLanguage(Request $request,$id)
    {
        $v = validator($request->all(), [
            'name' => 'required|max:50',
            'display_order' => 'required'
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        $language = Language::find($id);
        if (!$language) return $this->sendResponse(false,'Language not found');
        $input = $request->all();
        $input['updated_by'] = auth()->user()->id;
        $language->update($input);
        return $this->sendResponse(true,'success',$language);
    }

    /** delete Language */
    public function deleteLanguage($id)
    {
        $language = Language::find($id);
        if (!$language) return $this->sendResponse(false,'Language not found');
        $language->delete();
        return $this->sendResponse(true,'success',$language);
    }
}
