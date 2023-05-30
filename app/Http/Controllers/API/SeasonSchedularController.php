<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\SeasonSchedular;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;

class SeasonSchedularController extends Controller
{
    use Functions;

    public function create(Request $request)
    {
        $v = $this->checkValidation($request);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if(auth()->user()->type() === 'Instructor'){
            $contact_id = auth()->user()->contact_id;
        }else{
            if(!$request->contact_id)return $this->sendResponse(false, __('strings.required_validation', ['name' => 'contact id']));
            $contact_id = $request->contact_id;
        }

        $input_details = array();
        $i = 0;
        foreach($request->dates as $data){
            $input_details[$i]['contact_id'] = $contact_id;
            $input_details[$i]['start_date'] = $data['start_date'];
            $input_details[$i]['end_date'] = $data['end_date'];
            $input_details[$i]['start_time'] = $data['start_time'];
            $input_details[$i]['end_time'] = $data['end_time'];
            $input_details[$i]['created_at'] = date('Y-m-d H:i:s');
            $input_details[$i]['created_by'] = auth()->user()->id;
            
            if(isset($data['description'])){
                $input_details[$i]['description'] = $data['description'];
            }else{
                $input_details[$i]['description'] = null;
            }
            $i = $i + 1; 
        }
        SeasonSchedular::insert($input_details);
        return $this->sendResponse(true, __('strings.create_sucess', ['name' => 'Season schedular']));
    }

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1',
            'contact_id' => 'nullable|exists:contacts,id,category_id,2'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $season_schedular = SeasonSchedular::query();

        if(auth()->user()->type() === 'Instructor'){
            $contact_id = auth()->user()->contact_id;
        }else{
            if(!$request->contact_id)return $this->sendResponse(false, __('strings.required_validation', ['name' => 'contact id']));
            $contact_id = $request->contact_id;
        }

        $count = $season_schedular->count();
        
        if($request->page && $request->perPage){
            $page = $request->page;    
            $perPage = $request->perPage;    
            $season_schedular->skip($perPage*($page-1))->take($perPage);
        }

        $season_schedular = $season_schedular
        // ->with('contact')
        ->where('contact_id', $contact_id)
        ->orderBy('id','DESC')
        ->get();
        
        $data = [
            "season_schedular" => $season_schedular,
            "count" => $count
        ];

        return $this->sendResponse(true, __('strings.list_message', ['name' => 'Season schedular']), $data);
    }

    public function view($id)
    {
        $season_schedular = SeasonSchedular::find($id);
        if(!$season_schedular)return $this->sendResponse(false, __('strings.not_found_validation', ['name' => 'Season schedular']));
        return $this->sendResponse(true, __('strings.get_message', ['name' => 'Season schedular']), $season_schedular);
    }

    public function delete($id)
    {
        $season_schedular = SeasonSchedular::find($id);
        if(!$season_schedular)return $this->sendResponse(false, __('strings.not_found_validation', ['name' => 'Season schedular']));
        $season_schedular->delete();
        return $this->sendResponse(true, __('strings.delete_sucess', ['name' => 'Season schedular']));
    }

    public function update(Request $request)
    {
        $v = $this->checkValidation($request);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if(auth()->user()->type() === 'Instructor'){
            $contact_id = auth()->user()->contact_id;
        }else{
            if(!$request->contact_id)return $this->sendResponse(false, __('strings.required_validation', ['name' => 'contact id']));
            $contact_id = $request->contact_id;
        }

        $input_details = array();

        $i = 0;
        foreach($request->dates as $data){
            if(isset($data['action']) && $data['action'] === 'add'){
                $input_details[$i]['contact_id'] = $contact_id;
                $input_details[$i]['start_date'] = $data['start_date'];
                $input_details[$i]['end_date'] = $data['end_date'];
                $input_details[$i]['start_time'] = $data['start_time'];
                $input_details[$i]['end_time'] = $data['end_time'];
                $input_details[$i]['created_at'] = date('Y-m-d H:i:s');
                $input_details[$i]['created_by'] = auth()->user()->id;
                
                if(isset($data['description'])){
                    $input_details[$i]['description'] = $data['description'];
                }else{
                    $input_details[$i]['description'] = null;
                }
            }else{
                $season_schedular = SeasonSchedular::find($data['id']);
                $update_details['start_date'] = $data['start_date'];
                $update_details['end_date'] = $data['end_date'];
                $update_details['start_time'] = $data['start_time'];
                $update_details['end_time'] = $data['end_time'];
                
                if(isset($data['description'])){
                    $update_details['description'] = $data['description'];
                }else{
                    $update_details['description'] = null;
                }
                $season_schedular->update($update_details);
            }
            $i = $i + 1; 
        }
        if(count($input_details)){
            SeasonSchedular::insert($input_details);
        }
        if($request->delete_ids){
            SeasonSchedular::destroy($request->delete_ids);
        }

        return $this->sendResponse(true, __('strings.update_sucess', ['name' => 'Season schedular']));
    }

    public function checkValidation($request)
    {
        $v = validator($request->all(), [
            /**Validation rules */
            'contact_id' => 'nullable|exists:contacts,id,category_id,2',
            'dates' => 'nullable|array',
            'dates.*.start_date' => 'required|date_format:Y-m-d',
            'dates.*.end_date' => 'required|date_format:Y-m-d|after_or_equal:dates.*.start_date',
            'dates.*.start_time' => 'required|date_format:H:i:s',
            'dates.*.end_time' => 'required|date_format:H:i:s|after:dates.*.start_time',
            'dates.*.action' => 'nullable|in:add,edit',
            'dates.*.id' => 'exists:season_schedulars,id|required_if:dates.*.action,==,edit',
            'delete_ids' => 'nullable|array',
            'delete_ids.*' => 'exists:season_schedulars,id',
            'description' => 'nullable'
        ],[
            /**Validation messages */
            'dates.*.start_date.date_format' => __('validation.start_date_date_format'),
            'dates.*.start_date.required' => __('validation.required', ['attribute' => 'start date']),
            'dates.*.end_date.date_format' => __('validation.end_date_date_format'),
            'dates.*.end_date.required' => __('validation.required', ['attribute' => 'end date']),
            'dates.*.end_date.after_or_equal' => __('validation.end_date_after_or_equal'),
            'dates.*.start_time.date_format' => __('validation.start_time_date_format'),
            'dates.*.start_time.required' => __('validation.required', ['attribute' => 'start time']),
            'dates.*.end_time.date_format' => __('validation.end_time_date_format'),
            'dates.*.end_time.required' => __('validation.required', ['attribute' => 'end time']),
            'dates.*.end_time.after' => __('validation.end_time_after'),
            'dates.*.action.in' => __('validation.action_in'),
            'dates.*.id.required_if' => __('validation.id_required_if'),
            'dates.*.id.exists' => __('validation.id_exists'),
        ]);

        return $v;
    }

    /**Get instructor wise season schedulars */
    public function getSeasonSchedulars(Request $request)
    {
        /**Validation Rules */
        $v = validator($request->all(), [
            'date' => 'required|date|date_format:Y-m-d',
            'type' => 'required|in:day,week',
            'instructor_id' => 'nullable|exists:contacts,id,category_id,2,deleted_at,NULL'
        ]);

        /**If mismatch validation then return error response */
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $block_data = array();

        $schedular_data = SeasonSchedular::query();
        
        /**Day base filter */
        if ($request->type == 'day') {
            if ($request->date) {
                $request_date=$request->date;
                // $schedular_data = $schedular_data->where(function ($query) use ($request_date) {
                //     $query->where('start_date', '<=', $request_date);
                //     $query->where('end_date', '>=', $request_date);
                // })
                // ->orWhere(function ($query) use ($request_date) {
                //     $query->where('start_date', '<=', $request_date);
                //     $query->where('end_date', '>=', $request_date);
                // });
            }
        } elseif ($request->type == 'week') {
            /**Week base filter */
            if ($request->date) {
                $date = strtotime("+6 day", strtotime($request->date));
                $strt_date = $request->date;
                $end_date = date('Y-m-d', $date);
                
                // $schedular_data = $schedular_data->where(function($query) use($strt_date,$end_date){
                //     $query->whereBetween('start_date', [$strt_date,$end_date]);
                //     $query->orWhereBetween('end_date', [$strt_date,$end_date]);
                // });
            }
        }

        /**Instructor base filter */
        if ($request->instructor_id) {
            $schedular_data = $schedular_data->where('contact_id', $request->instructor_id);
        }

        /**Get details */
        if($schedular_data){
            $schedular_data = $schedular_data->with('contact')->get();
        }
        
        $data['schedular_data'] = $schedular_data;
        return $this->sendResponse(true, 'success', $data);
    }
}
