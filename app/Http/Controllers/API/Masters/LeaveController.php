<?php

namespace App\Http\Controllers\API\Masters;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Functions;
use App\Models\Leave;

class LeaveController extends Controller
{
    use Functions;

    /** Get Leaves Master  */
    public function getLeaves()
    {
        $Leaves = Leave::where('is_active',true)->get();
        return $this->sendResponse(true,'success',$Leaves);
    }
}
