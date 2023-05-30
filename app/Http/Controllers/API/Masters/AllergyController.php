<?php

namespace App\Http\Controllers\API\Masters;

use App\Models\Allergy;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;

class AllergyController extends Controller
{
    use Functions;
    
    /** Get all Allergies */
    public function getAllergies()
    {
        $allergies = Allergy::orderBy('is_system','DESC')->orderBy('created_at','DESC')->get();
        return $this->sendResponse(true,'List of all allergies',$allergies);
    }

    /** Create new Allergy */
    public function createAllergy(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required|max:50',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        $input = $request->all();
        $input['created_by'] = auth()->user()->id;
        $allergy = Allergy::create($input);
        return $this->sendResponse(true,'success',$allergy);
    }

    /** Update Allergy */
    public function updateAllergy(Request $request,$id)
    {
        $v = validator($request->all(), [
            'name' => 'required|max:50',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        $allergy = Allergy::find($id);
        if (!$allergy) return $this->sendResponse(false,'Allergy not found');
        $input = $request->all();
        $input['updated_by'] = auth()->user()->id;
        $allergy->update($input);
        return $this->sendResponse(true,'success',$allergy);
    }

    /** delete Allergy */
    public function deleteAllergy($id)
    {
        $allergy = Allergy::find($id);
        if (!$allergy) return $this->sendResponse(false,'Allergy not found');
        $allergy->delete();
        return $this->sendResponse(true,'success',$allergy);
    }
}
