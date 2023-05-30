<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\SubChild\SubChildContact;
use App\Models\SubChild\SubChildContactAllergy;
use App\Models\SubChild\SubChildContactLanguage;

class SubChildContactController extends Controller
{
    use Functions;

    /*Add sub child contact */
    public function addSubChild(Request $request)
    {
        $v = $this->checkValidation($request);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        /**Get neccessary details */
        $input_details = $request->only('contact_id','first_name','last_name','email','mobile1','mobile2','address','zip','dob','country','gender','accomodation','skiing_level','city','accommodation_id');

        /**Create new sub child contact */
        $sub_contact = SubChildContact::create($input_details);

        if($request->allergies) {
            foreach ($request->allergies as $allergy) {
                SubChildContactAllergy::create(
                [
                    'sub_childe_contact_id' => $sub_contact->id,
                    'allergy_id' => $allergy
                ]);
            }
        }

        if($request->languages) {
            foreach ($request->languages as $language) {
                SubChildContactLanguage::create(
                [
                    'sub_childe_contact_id' => $sub_contact->id,
                    'language_id' => $language
                ]);
            }
        }

        /**Add crm user action trail */    
        // if($sub_contact){
        //     $action_id = $sub_contact->id; //contact created id
        //     $action_type = 'A'; //A = Added
        //     $module_id = 9; //module id base module table 
        //     $module_name = "Sub Contacts"; //module name base module table
        //     $trail = $this->addCrmUserActionTrail($action_id,$action_type,$module_id,$module_name);
        // }
        /**End manage trail */

        $data = [
            "sub_child_contact_id" => $sub_contact->id,
            "first_name" => $sub_contact->first_name,
            "last_name" => $sub_contact->last_name,
        ];

        /**Return success resposne with details */
        return $this->sendResponse(true,__('strings.create_sucess',['name' => 'Sub child']), $data);
    }

    /**List */
    public function subChildList(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1',
            'contact_id' => 'nullable|exists:contacts,id',
            'first_name' => 'nullable',
            'last_name' => 'nullable'
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $sub_childs = SubChildContact::query();

        if($request->contact_id){
            $sub_childs = $sub_childs->where('contact_id', $request->contact_id);
        }

        if($request->first_name){
            $sub_childs = $sub_childs->where('first_name', 'like', "%$request->first_name%");
        }
        
        if($request->last_name){
            $sub_childs = $sub_childs->where('last_name', 'like', "%$request->last_name%");
        }

        $sub_childs_count = $sub_childs->count();

        if($request->page && $request->perPage){
            $page = $request->page;
            $perPage = $request->perPage;
            $sub_childs = $sub_childs->skip($perPage*($page-1))->take($perPage);
        }

        $sub_childs = $sub_childs
        ->with('contact')
        ->with('allergies.allergy')
        ->with('languages.language')
        ->with('accommodation_data')
        ->get();

        $data = [
            'sub_childs' => $sub_childs,
            'count' => $sub_childs_count
        ];
        return $this->sendResponse(true,'success',$data);
    }

    /*Update sub child contact */
    public function updateSubChild(Request $request, $id)
    {
        $v = $this->checkValidation($request);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $sub_contact = SubChildContact::find($id);

        if(!$sub_contact){
            return $this->sendResponse(false,__('strings.not_found_validation', ['name' => 'Sub child']));
        }
        /**Get neccessary details */
        $input_details = $request->only('contact_id','first_name','last_name','email','mobile1','mobile2','address','zip','dob','country','gender','accomodation','skiing_level','city','accommodation_id');

        /**Create new sub child contact */
        $sub_contact->update($input_details);

        if($request->allergies) {
            SubChildContactAllergy::where('sub_childe_contact_id',$id)->delete();
            foreach ($request->allergies as $allergy) {
                SubChildContactAllergy::create(
                [
                    'sub_childe_contact_id' => $id,
                    'allergy_id' => $allergy
                ]);
            }
        }

        if($request->languages) {
            SubChildContactLanguage::where('sub_childe_contact_id',$id)->delete();
            foreach ($request->languages as $language) {
                SubChildContactLanguage::create(
                [
                    'sub_childe_contact_id' => $id,
                    'language_id' => $language
                ]);
            }
        }

        /**Add crm user action trail */    
        // if($sub_contact){
        //     $action_id = $sub_contact->id; //contact created id
        //     $action_type = 'U'; //U = Update
        //     $module_id = 9; //module id base module table 
        //     $module_name = "Sub Contacts"; //module name base module table
        //     $trail = $this->addCrmUserActionTrail($action_id,$action_type,$module_id,$module_name);
        // }
        /**End manage trail */

        $data = [
            "sub_child_contact_id" => $id,
            "first_name" => $sub_contact->first_name,
            "last_name" => $sub_contact->last_name,
        ];

        /**Return success resposne with details */
        return $this->sendResponse(true,__('strings.update_sucess',['name' => 'Sub child']), $data);
    }

    /**Validation rules */
    public function checkValidation($request)
    {
        $v = validator($request->all(), [
            'contact_id' => 'nullable|exists:contacts,id',
            'first_name'=>'nullable',
            'last_name'=>'nullable',
            'email' => 'nullable|email|unique:sub_child_contacts,email',
            'mobile1' => 'max:25',
            'mobile2' => 'max:25',
            'address' => 'nullable',
            'zip' => 'nullable',
            'dob'=>'nullable|date_format:Y-m-d',
            'city' => 'nullable',
            'country' => 'nullable|exists:countries,id',
            'gender' => 'in:M,F,O',
            'accomodation' => 'nullable',
            'skiing_level' => 'nullable|in:Kinderland,Unknown,Green,Blue,Red,Black',
            'allergies' => 'nullable|array',
            'allergies.*' => 'exists:allergies,id',
            'languages' => 'nullable|array',
            'languages.*' => 'exists:languages,id',
            'accommodation_id' => 'nullable|integer'
        ],[
            'dob.date_format' => __('validation.dob_invalid_formate'),
            'allergies.*.exists' =>  __('strings.exist_validation', ['name' => 'allergies']),
            'languages.*.exists' =>  __('strings.exist_validation', ['name' => 'languages'])
        ]);

        return $v;
    }
}
