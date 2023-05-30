<?php

namespace App\Http\Controllers\API\Masters;

use App\Exports\MeetingPointExport;
use App\MeetingPoint;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class MeetingPointController extends Controller
{
    use Functions;

    /** Create new Meeting Point */
    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required|max:250',
            'address' => 'required|max:250',
            'lat' => 'required|numeric',
            'long' => 'required|numeric',
        ],
        [
            'lat.required' => 'Please provide a valid address',
            'long.required' => 'Please provide a valid address',
        ]
    );
        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $input = $request->all();
        $meeting_point = MeetingPoint::create($input);
        return $this->sendResponse(true,'success',$meeting_point);
    }

    /** Update Meeting Point */
    public function update(Request $request,$id)
    {
        $v = validator($request->all(), [
            'name' => 'required|max:250',
            'address' => 'required|max:250',
            'lat' => 'required|numeric',
            'long' => 'required|numeric',
        ],
        [
            'lat.required' => 'Please provide a valid address',
            'long.required' => 'Please provide a valid address',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        $meeting_point = MeetingPoint::find($id);
        if (!$meeting_point) return $this->sendResponse(false,'Meeting Point not found');
        $input = $request->all();
        $meeting_point->update($input);
        return $this->sendResponse(true,'success',$meeting_point);
    }

    /** delete Meeting Point */
    public function delete($id)
    {
        $meeting_point = MeetingPoint::find($id);
        if (!$meeting_point) return $this->sendResponse(false,'Meeting Point not found');
        $meeting_point->delete();
        return $this->sendResponse(true,'success',$meeting_point);
    }

    /** Get all Meeting Points with paginations and search */
    public function list(Request $request)
    {
        /** If Export Data to csv request ignore validation */
        if(!$request->is_export)
        {

        $v = validator($request->all(), [
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        }
        
        if($request->page && $request->perPage){
            $page = $request->page;    
            $perPage = $request->perPage; 
        }

        $meeting_point = MeetingPoint::select('id','name','address','lat','long','is_active');
        $meeting_point_count = $meeting_point->count();

        if($request->search) {
            $search = $request->search;
            $meeting_point = $meeting_point->where(function($query) use($search){
                $query->where('name','like',"%$search%");
            });
            $meeting_point_count = $meeting_point->count();
        }
       
        if($request->is_active) {
            $meeting_point = $meeting_point->where('is_active',1);
            $meeting_point_count = $meeting_point->count();
        }elseif($request->page && $request->perPage){
            $meeting_point->skip($perPage*($page-1))->take($perPage);
        }

        $meeting_point = $meeting_point->latest()->get();

        /*** For Export Data To CSV  ***/
        if ($request->is_export) {
            return Excel::download(new MeetingPointExport($meeting_point->toArray()), 'Meeting Points.csv');
        }
        /* End Export to CSV */

        $data = [
            'meeting_point' => $meeting_point,
            'count' => $meeting_point_count
        ];
        return $this->sendResponse(true,'success',$data);
    }

    /** API for Active/Inactive Meeting point */
    public function changeStatus(Request $request,$id)
     {
        $v = validator($request->all(), [
            'is_active' => 'boolean',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $meeting_point = MeetingPoint::find($id);
        if (!$meeting_point) return $this->sendResponse(false,'Meeting Point not found');
        
        $meeting_point->is_active = $request->is_active;
        $meeting_point->save();
        return $this->sendResponse(true,__('strings.meeting_point_status_change_success'),$meeting_point);
     }

     /** API for View Meeting point details */
    public function view($id)
     {
        $meeting_point = MeetingPoint::find($id);
        if (!$meeting_point) return $this->sendResponse(false,'Meeting Point not found');
        return $this->sendResponse(true,'success',$meeting_point);
     }
}
