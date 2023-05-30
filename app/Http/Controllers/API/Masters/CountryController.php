<?php

namespace App\Http\Controllers\API\Masters;

use App\Models\Country;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;

class CountryController extends Controller
{
    use Functions;
    
    /** Get all countries */
    public function getCountries()
    {
        //$countries = Country::latest()->get();
        $countries = Country::orderBy('name')->get();
        return $this->sendResponse(true,'List of countries',$countries);
    }

    /** Create new Country */
    public function createCountry(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required|max:50|unique:countries',
            'code' => 'required|max:3|unique:countries',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $input = $request->all();
        $input['created_by'] = auth()->user()->id;
        $country = Country::create($input);
        return $this->sendResponse(true,'success',$country);
    }

    /** Update Country */
    public function updateCountry(Request $request,$id)
    {
        $v = validator($request->all(), [
            'name' => 'required|max:50',
            'code' => 'required|max:3',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        $authId = auth()->user()->id;
        $checkCount = Country::where('code',$request->code)->where('id','!=',$id)->count();
        if($checkCount > 0) return $this->sendResponse(false,'The code has already been taken.');
        $country = Country::find($id);
        if (!$country) return $this->sendResponse(false,'Country not found');
        $input = $request->all();
        $input['updated_by'] = $authId;
        $country->update($input);
        return $this->sendResponse(true,'success',$country);
    }

    /** delete Country */
    public function deleteCountry($id)
    {
        $country = Country::find($id);
        if (!$country) return $this->sendResponse(false,'Country not found');
        $country->delete();
        return $this->sendResponse(true,'success',$country);
    }
}
