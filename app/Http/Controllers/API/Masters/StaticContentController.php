<?php

namespace App\Http\Controllers\API\Masters;

use Illuminate\Http\Request;
use App\Models\StaticContent;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;

class StaticContentController extends Controller
{
    use Functions;
    
    /** Update Static Content */
    public function updateStaticContent(Request $request,$type)
    {
        $v = validator($request->all(), [
            'description' => 'required',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        $staticContent = StaticContent::where('type',$type)->first();
        if (!$staticContent) return $this->sendResponse(false,'StaticContent not found');
        $input = $request->all();
        $staticContent->update($input);
        return $this->sendResponse(true,'success',$staticContent);
    }

    /** Get Static Content */
    public function getStaticContent()
    {
        $staticContent = StaticContent::get();
        return $this->sendResponse(true,'success',$staticContent);
    }

}
