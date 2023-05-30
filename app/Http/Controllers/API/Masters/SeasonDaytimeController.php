<?php

namespace App\Http\Controllers\API\Masters;

use App\Models\SeasonDaytimeMaster;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;

class SeasonDaytimeController extends Controller
{
     use Functions;

     /**Update Season and Daytime */
     public function updateSeason(Request $request){
       /*  $v = validator($request->all(), [
            'name' => 'required|max:20',
            'start_date' => 'required_with:end_date|date',
            'end_date' => 'required_with:start_date|date',
            'start_time' => 'required_with:end_time|date_format:H:i:s',
            'end_time' => 'required_with:start_time|date_format:H:i:s',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input = $request->all();

        if($request->start_date && $request->end_date && $request->start_time && $request->end_time){
            return $this->sendResponse(false, __('strings.season_daytime_invalid_input'));
        }

        if(empty($request->start_date) && empty($request->end_date)){
            $input['start_date'] = null;
            $input['end_date'] = null;
        }elseif(empty($request->start_time) && empty($request->end_time)){
            $input['start_time'] = null;
            $input['end_time'] = null;
        }

        $season = SeasonDaytimeMaster::find($id);
        if (!$season) {
            return $this->sendResponse(false, __('strings.season_daytime_not_found'));
        }
        
        $season->update($input);
        return $this->sendResponse(true, __('strings.season_daytime_update_success')); */
        
        //truncate table
        SeasonDaytimeMaster::truncate();

        foreach ($request->data as $data) {

            unset($data['updated_at']);

            if(isset($data['is_delete']) && $data['is_delete']==1){
                /**If data is_delete 1 then delete the data */
                $season = SeasonDaytimeMaster::find($data['id']);
                if (!$season) {
                    continue;
                }
                $season->delete();
            }else{
                /**If id find then update the data other wise add new data */
                $season = SeasonDaytimeMaster::updateOrCreate(
                    ['id' => $data['id']],
                    $data
                   );
            }
        }
        return $this->sendResponse(true, __('strings.season_daytime_update_success'));

     }

     /**View Season and Daytime */
     public function view($id){
        $season = SeasonDaytimeMaster::find($id);
        if (!$season) {
            return $this->sendResponse(false, __('strings.season_daytime_not_found'));
        }
        return $this->sendResponse(true, 'success', $season);
    }

     /**List Season and Daytime */
     public function list(){
        $season = SeasonDaytimeMaster::get();
        return $this->sendResponse(true, 'success', $season);
    }
}
