<?php

namespace App\Http\Controllers\API\Masters;

use App\Models\Office;
use Illuminate\Http\Request;
use App\Models\SequenceMaster;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Finance\Cash;

class OfficeController extends Controller
{
    use Functions;

    /** Get all Offices/Branch */
    public function getOffices()
    {
        $Offices = Office::with(['country_detail'=>function ($query) {
            $query->select('id', 'name', 'code');
        },'account'])->latest()->get();
        return $this->sendResponse(true, 'success', $Offices);
    }

    /** Create new Office */
    public function createOffice(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required|max:100',
            'day_start_sum' => 'required',
            'street_address1' => 'required|max:250',
            'city' => 'required|max:50',
            'state' => 'required|max:50',
            'country' => 'required|min:1|integer',
            'is_head_office' => 'boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $input = $request->all();
        $input['created_by'] = auth()->user()->id;
        $office = Office::create($input);
        if ($request->is_head_office) {
            Office::where('id', '!=', $office->id)->update(['is_head_office'=>false]);
        }

        // update opening balance if changed
        if ($office->opening_balance > 0) {
            $office->getAccount()->updateOpeningBalance($office->opening_balance);
        }

        return $this->sendResponse(true, 'success', $office);
    }

    /** Update Office */
    public function updateOffice(Request $request, $id)
    {
        $v = validator($request->all(), [
            'name' => 'required|max:100',
            'day_start_sum' => 'required',
            'street_address1' => 'required|max:250',
            'city' => 'required|max:50',
            'state' => 'required|max:50',
            'country' => 'required|min:1|integer',
            'is_head_office' => 'boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $office = Office::find($id);
        if (!$office) {
            return $this->sendResponse(false, 'Office not found');
        }
        $old_opening_balance = $office->opening_balance;

        $input = $request->all();
        $input['updated_by'] = auth()->user()->id;
        $office->update($input);

        if ($request->is_head_office) {
            Office::where('id', '!=', $id)->update(['is_head_office'=>false]);
        }

        // update opening balance if changed
        if ($old_opening_balance != $request->opening_balance) {
            $office->getAccount()->updateOpeningBalance($request->opening_balance, $old_opening_balance);

            $difference_in_balance = $request->opening_balance - $old_opening_balance;

            // update all the records of the office
            Cash::where('office_id', $office->id)
                ->increment('running_amount', $difference_in_balance);
        }
        return $this->sendResponse(true, 'success', $office);
    }

    /** delete Office */
    public function deleteOffice($id)
    {
        $office = Office::find($id);
        if (!$office) {
            return $this->sendResponse(false, 'Office not found');
        }
        $office->delete();
        return $this->sendResponse(true, 'success', $office);
    }

    /** Get VAT percentage */
    public function getVat()
    {
        $vat = SequenceMaster::select('id', 'sequence as percentage')->where('code', 'VAT')->first();

        $lvat = SequenceMaster::select('id', 'sequence as percentage')->where('code', 'LVAT')->first();

        $data = [
            "VAT" => $vat,
            "LVAT" => $lvat,
        ];

        return $this->sendResponse(true, 'success', $data);
    }

    /** Set VAT percentage */
    public function setVat(Request $request)
    {
        $v = validator($request->all(), [
            'percentage' => 'required|numeric|min:0|max:100',
            'lpercentage' => 'required|numeric|min:0|max:100'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $vat = SequenceMaster::where('code', 'VAT')->update(['sequence'=>$request->percentage]);

        $vat = SequenceMaster::where('code', 'LVAT')->update(['sequence'=>$request->lpercentage]);

        return $this->sendResponse(true, 'success');
    }

    public function view($id)
    {
        $Office = Office::with(['country_detail'=>function ($query) {
            $query->select('id', 'name', 'code');
        },'account'])
        ->find($id);

        if(!$Office)
            return $this->sendResponse(false, __('strings.not_found_validation',['name' => 'Office']));

        return $this->sendResponse(true, 'success', $Office);
    }
}
