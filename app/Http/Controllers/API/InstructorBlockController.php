<?php

namespace App\Http\Controllers\API;

use Excel;
use App\MeetingPoint;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Models\InstructorBlock;
use App\Models\InstructorBlockMap;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Exports\InstructorBlockExport;
use App\Models\BookingProcess\BookingProcesses;
use App\Models\BookingProcess\BookingProcessInstructorDetails;

class InstructorBlockController extends Controller
{
    use Functions;

    /**
     * Description : Below APIs are for instructor block module 
     * Date : 24-08-2020
     */

    /**Add instructor block */
    public function create(Request $request){
        /**Validation Rules */
        $v = validator($request->all(), [
            'instructor_id' => 'required|array|exists:contacts,id,category_id,2,deleted_at,NULL',
            'title' => 'nullable|max:100',
            'start_date' => 'required|date|date_format:Y-m-d|before_or_equal:end_date',
            'end_date' => 'required|date|date_format:Y-m-d|after_or_equal:start_date',
            'start_time' => 'required|date_format:H:i|before:end_time',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'description' => 'nullable|max:255',
            'block_color' => 'nullable|max:20',
            'amount' => 'nullable|max:20',
            'is_paid' => 'nullable|boolean',
            'meeting_point' => 'nullable|exists:meeting_points,id,deleted_at,NULL',
            'meeting_point_other_name' => 'nullable',
            'block_label_id' => 'nullable|exists:block_labels,id,deleted_at,NULL'
        ]);
        /**If mismatch validation then return error response */
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        /**Get necessary details */
        $instructor_block_input = $request->only('title','start_date','end_date','start_time','end_time','description','block_color','amount','is_paid','meeting_point','meeting_point_other_name', 'block_label_id');

        /**For multiple instructor add block data */
        foreach($request->instructor_id as $instructor){
            /**Create */
            $instructor_block_input['instructor_id'] = $instructor;
            $instructor_block = InstructorBlock::create($instructor_block_input);
            /**Add/ Update instructor block map details */
            $this->addInstructorBlockMapDetails($instructor_block_input, $instructor_block->id);
            
            /**Add crm user action trail */
            if ($instructor_block) {
                $action_id = $instructor_block->id; //instructor block id
                $action_type = 'A'; //A = Add
                $module_id = 31; //module id base module table
                $module_name = "Instructor block"; //module name base module table
                $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
            }
            /**End manage trail */
        }

        /**Return success response */
        return $this->sendResponse(true, __('strings.create_sucess', ['name' => 'Instructor block']));
    }

    /**Update instructor block */
    public function update(Request $request, $id){
        /**Validation Rules */
        $v = validator($request->all(), [
            'instructor_id' => 'required|exists:contacts,id,category_id,2,deleted_at,NULL',
            'title' => 'nullable|max:100',
            'start_date' => 'required|date|date_format:Y-m-d|before_or_equal:end_date',
            'end_date' => 'required|date|date_format:Y-m-d|after_or_equal:start_date',
            'start_time' => 'required|date_format:H:i|before:end_time',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'description' => 'nullable|max:255',
            'block_color' => 'nullable|max:20',
            'amount' => 'nullable|max:20',
            'is_paid' => 'nullable|boolean',
            'meeting_point' => 'nullable|exists:meeting_points,id,deleted_at,NULL',
            'meeting_point_other_name' => 'nullable',
            'block_label_id' => 'nullable|exists:block_labels,id,deleted_at,NULL'
        ]);
        /**If mismatch validation then return error response */
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        /**Get necessary details */
        $instructor_block_update = $request->only('instructor_id','title','start_date','end_date','start_time','end_time','description','block_color','amount','is_paid','meeting_point','meeting_point_other_name','block_label_id');

        /**Check valid or not if not then error response */
        $instructor_block = InstructorBlock::find($id);

        if(!$instructor_block)return $this->sendResponse(false, __('strings.not_found_validation', ['name' => 'Instructor block']));

        if(!isset($instructor_block_update['block_label_id'])){
            $instructor_block_update['block_label_id'] = null;
        }
        /**Update */
        $instructor_block->update($instructor_block_update);
        /**Add/ Update instructor block map details */
        $this->addInstructorBlockMapDetails($instructor_block_update, $id);

        /**Update crm user action trail */
        if ($instructor_block) {
            $action_id = $instructor_block->id; //instructor block id
            $action_type = 'U'; //U = Updated
            $module_id = 31; //module id base module table
            $module_name = "Instructor block"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */
        
        /**Return success response */
        return $this->sendResponse(true, __('strings.update_sucess', ['name' => 'Instructor block']));
    }

    /**Update instructor block */
    public function list(Request $request){
        /**Validation Rules */
        $v = validator($request->all(), [
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'paid_status' => 'nullable|boolean',
            'start_time' => 'nullable|date_format:H:i:s',
            'end_time' => 'nullable|date_format:H:i:s',
        ]);

        /**If mismatch validation then return error response */
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $instructor_blocks = InstructorBlock::query();

        if(auth()->user()->is_app_user && auth()->user()->type() === 'Instructor'){
            $instructor_blocks = $instructor_blocks->where('instructor_id', auth()->user()->contact_id);
        }

        /**Filter paid status */
        if(isset($request->paid_status)){
            $paid_status = $request->paid_status;
            $instructor_blocks = $instructor_blocks->where('is_paid', $paid_status);
        }

        /**Filter date */
        if($request->date){
            $date = $request->date;
            $instructor_blocks = $instructor_blocks->where(function($query) use($date){
                $query->where('date','like',"%$date%");
            });
        }

        /**Filter start time and end time */
        if($request->start_time && $request->end_time){
            $start_time = $request->start_time;
            $end_time = $request->end_time;
            $instructor_blocks = $instructor_blocks->where(function($query) use($start_time, $end_time){
                $query->whereTime('start_time','>=',$start_time);
                $query->whereTime('end_time','<=',$end_time);
            });
        }

        /**Normal search */
        if($request->search){
            $search = $request->search;
            /**Search instructor base */
            $instructor_ids = Contact::where('category_id',2)
                ->where(function($query) use($search){
                $query->where('email','like',"%$search%");
                $query->orWhere('mobile1','like',"%$search%");
                $query->orWhere('salutation','like',"%$search%");
                $query->orWhere('first_name','like',"%$search%");
                $query->orWhere('middle_name','like',"%$search%");
                $query->orWhere('last_name','like',"%$search%");
            })->pluck('id');

            /**Search meeting point base */
            $meeting_point_ids = MeetingPoint::where(function($query) use($search){
                $query->where('name','like',"%$search%");
                $query->orWhere('address','like',"%$search%");
            })->pluck('id');

            $instructor_blocks = $instructor_blocks
            ->whereIn('instructor_id',$instructor_ids)
            ->orWhereIn('meeting_point',$meeting_point_ids)
            ->orWhere(function($query) use($search){
                $query->where('title','like',"%$search%");
                $query->orWhere('amount','like',"%$search%");
            });
        }

        /**Get total records count */
        $instructor_blocks_count = $instructor_blocks->count();

        /**For pagination */
        if($request->page && $request->perPage){
            $page = $request->page;
            $perPage = $request->perPage;
            $instructor_blocks->skip($perPage*($page-1))->take($perPage);
        }

        /**Get instructor details with sub details */
        $instructor_blocks = $instructor_blocks->with('instructor_details')
        ->with('meeting_point_details')
        ->get();

        $data = [
            'instructor_blocks' => $instructor_blocks,
            'count' => $instructor_blocks_count
        ];

        /**Export list */
        if ($request->is_export) {
            return Excel::download(new InstructorBlockExport($instructor_blocks->toArray()), 'InstructorBlock.csv');
        }

        /**Return success response with details */
        return $this->sendResponse(true,__('strings.list_message',['name' => 'Instructor block']),$data);
    }

    /**Update instructor block */
    public function view($id){
        /**Check valid or not if not then error response */
        $instructor_block = InstructorBlock::with('instructor_details')
        ->with('meeting_point_details')
        ->find($id);

        if(!$instructor_block)return $this->sendResponse(false, __('strings.not_found_validation', ['name' => 'Instructor block']));

        /**Return success response with details */
        return $this->sendResponse(true,__('strings.get_message',['name' => 'Instructor block']),$instructor_block);
    }

    /**Delete instructor block */
    public function delete($id){
        /**Check valid or not if not then error response */
        $instructor_block = InstructorBlock::find($id);

        if(!$instructor_block)return $this->sendResponse(false, __('strings.not_found_validation', ['name' => 'Instructor block']));

        /**Delete crm user action trail */
        if ($instructor_block) {
            $action_id = $instructor_block->id; //instructor block id
            $action_type = 'D'; //D = Deleted
            $module_id = 31; //module id base module table
            $module_name = "Instructor block"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        InstructorBlockMap::where('instructor_block_id', $id)->delete();
        /**Get instructor details with sub details */
        $instructor_block = $instructor_block->delete();

        /**Return success response with details */
        return $this->sendResponse(true,__('strings.delete_sucess',['name' => 'Instructor block']));
    }

    /**Check block available instructor block */
    public function checkBlockAvailable(Request $request){
        /**Validation Rules */
        $v = validator($request->all(), [
            'instructor_id' => 'required',
            'start_date' => 'required|date|date_format:Y-m-d',
            'end_date' => 'required|date|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s',
            'type' => 'required|in:block,booking'
        ]);

        /**If mismatch validation then return error response */
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $instructor_id = $request->instructor_id;
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $start_time = $request->start_time;
        $end_time = $request->end_time;
        $available_status = false;
        $instructor_blocks = [];
        $available_data = [];
        
        /**If block exist ot not */
        if($request->type == 'block'){
            /**Date and time base filter */
            $instructor_block_id = InstructorBlockMap::where('is_release', false)
            ->where('instructor_id', $instructor_id)
            ->whereBetween('start_date', [$start_date, $end_date])
            ->where(function ($q) use ($start_time,$end_time) {
                $q->where('start_time', '<=', $start_time);
                $q->OrWhere('start_time', '<=', $end_time);
            })
            ->where(function ($q) use ($start_time,$end_time) {
                $q->where('end_time', '>=', $start_time);
                $q->OrWhere('end_time', '>=', $end_time);
            })
            ->pluck('instructor_block_id');

            $instructor_blocks = InstructorBlock::whereIn('id', $instructor_block_id)
            ->pluck('title');

            if(count($instructor_blocks)){
                $available_status = true;
                $available_data = $instructor_blocks;
            }

        }else{
            /**If booking exist or not */
            $dates_data[] = [
                'StartDate_Time' => $start_date.' '.$start_time,
                'EndDate_Time' => $end_date.' '.$end_time
            ];
            /**Get available booking ids from same date and times */
            $checkAvailability = $this->getAvailableInstructorListNew($dates_data);

            if(empty($checkAvailability['booking_processes_ids_main'])){
                $available_status = false;
            }else{
                $booking_ids = $checkAvailability['booking_processes_ids_main'];
                $instructor_base_bookings = BookingProcessInstructorDetails::whereIn('booking_process_id', $booking_ids)
                ->whereIn('contact_id', $instructor_id)
                ->pluck('booking_process_id');

                if(count($instructor_base_bookings)){
                    $booking_numbers = BookingProcesses::whereIn('id', $instructor_base_bookings)->pluck('booking_number');
                    $available_status = true;
                    $available_data = $booking_numbers;
                }
            }
        }

        $data = [
            'available_status' => $available_status,
            'available_data' => $available_data
        ];

        /**Return success response with details */
        return $this->sendResponse(true,__('strings.get_message',['name' => 'Available']),$data);
    }

    /**Get instructor blocks
     *payload : id - instructor id 
     */
    public function getBlocks(Request $request)
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

        /**Day base filter */
        if ($request->type == 'day') {
            if ($request->date) {
                $request_date=$request->date;
                $block_data = InstructorBlockMap::where('start_date', $request_date)
                ->where('is_release', false);
            }
        } elseif ($request->type == 'week') {
            /**Week base filter */
            if ($request->date) {
                $date = strtotime("+6 day", strtotime($request->date));
                $start_date = $request->date;
                $end_date = date('Y-m-d', $date);
                
                $block_data = InstructorBlockMap::where('is_release', false)
                ->where(function($query) use($start_date,$end_date){
                    $query->whereBetween('start_date', [$start_date,$end_date]);
                });
            }
        }

        /**Instructor base filter */
        if ($request->instructor_id) {
            $block_data = $block_data->where('instructor_id', $request->instructor_id);
        }

        /**Get details */
        if($block_data){
            $block_data = $block_data->with('instructor_blocks.instructor_details','instructor_blocks.meeting_point_details')->get();
        }
        
        $data['block_data'] = $block_data;
        return $this->sendResponse(true, 'success', $data);
    }

    public function addInstructorBlockMapDetails($instructor_block_input, $instructor_block_id)
    {
        $data['instructor_id'] = $instructor_block_input['instructor_id'];
        $data['instructor_block_id'] = $instructor_block_id;
        $start_date = $instructor_block_input['start_date'];
        $end_date = $instructor_block_input['end_date'];

        while (strtotime($start_date) <= strtotime($end_date)) {
            $data['start_date'] = $start_date;
            $data['start_time'] = $instructor_block_input['start_time'];
            $data['end_time'] = $instructor_block_input['end_time'];
            $block_exist = InstructorBlockMap::where('instructor_block_id', $data['instructor_block_id'])->where('instructor_id', $data['instructor_id'])->where('start_date', $start_date)->first();
            if (!$block_exist) {
                InstructorBlockMap::create($data);
            } else {
                $old_block_exist = InstructorBlockMap::where('instructor_block_id', $data['instructor_block_id'])
                ->where('instructor_id', $data['instructor_id'])
                ->where('start_date', '<', $instructor_block_input['start_date'])
                ->first();
                if($old_block_exist){
                    $old_block_exist->delete();
                }

                $block_exist->update($data);
            }
            $start_date = date("Y-m-d", strtotime("+1 day", strtotime($start_date)));
        }

        return 1;
    }

    /**Delete multiple instructor block */
    public function deleteMultiple(Request $request){
        /**Validation Rules */
        $v = validator($request->all(), [
            'block_ids' => 'required|array',
            'block_ids.*' => 'required|exists:instructor_blocks,id',
            'is_similar_delete' => 'nullable|boolean'
        ],[
            'block_ids.*.exists' => __('strings.exist_validation', ['name' => 'Instructor block'])
        ]);

        /**If mismatch validation then return error response */
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if($request->is_similar_delete){
            foreach($request->block_ids as $id){
                $instructor_block = InstructorBlock::find($id);
                if($instructor_block){
                    $instructor_block_ids = InstructorBlock::where('title', $instructor_block->title)
                    ->whereDate('start_date', $instructor_block->start_date)
                    ->whereDate('end_date', $instructor_block->end_date)
                    ->pluck('id');

                    if(count($instructor_block_ids)){
                        InstructorBlock::whereIn('id', $instructor_block_ids)->delete();
                        InstructorBlockMap::whereIn('instructor_block_id', $instructor_block_ids)->delete();
                    }
                }
            }
        }else{
            InstructorBlock::destroy($request->block_ids);
            InstructorBlockMap::where('instructor_block_id', $request->block_ids)->delete();
        }

        /**Delete crm user action trail */
        foreach($request->block_ids as $id){
            $action_id = $id; //instructor block id
            $action_type = 'D'; //D = Deleted
            $module_id = 31; //module id base module table
            $module_name = "Instructor block"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */
        
        /**Return success response with details */
        return $this->sendResponse(true,__('strings.delete_sucess',['name' => 'Instructor block']));
    }

    /**Update multiple instructor block */
    public function updateMultiple(Request $request)
    {
        /**Validation Rules */
        $v = validator($request->all(), [
            'block_id' => 'required|exists:instructor_blocks,id',
            'is_similar_update' => 'nullable|boolean',
            'start_time' => 'required|date_format:H:i|before:end_time',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        /**If mismatch validation then return error response */
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $id = $request->block_id;
        if($request->is_similar_update){
            $instructor_block = InstructorBlock::find($id);
            if($instructor_block){
                $instructor_block_ids = InstructorBlock::where('title', $instructor_block->title)
                ->whereDate('start_date', $instructor_block->start_date)
                ->whereDate('end_date', $instructor_block->end_date)
                ->pluck('id');

                if(count($instructor_block_ids)){
                    InstructorBlock::whereIn('id', $instructor_block_ids)->update(['start_time' => $request->start_time, "end_time" => $request->end_time]);
                    InstructorBlockMap::whereIn('instructor_block_id', $instructor_block_ids)->update(['start_time' => $request->start_time, "end_time" => $request->end_time]);
                }
            }
        }else{
            InstructorBlock::where('id', $id)->update(['start_time' => $request->start_time, "end_time" => $request->end_time]);
            InstructorBlockMap::where('instructor_block_id', $id)->update(['start_time' => $request->start_time, "end_time" => $request->end_time]);
        }
        /**Return success response */
        return $this->sendResponse(true, __('strings.update_sucess', ['name' => 'Instructor block']));
    }
    /**End : instructor block module*/
}
