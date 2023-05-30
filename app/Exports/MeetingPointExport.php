<?php
namespace App\Exports;

use App\Models\Contact;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class MeetingPointExport implements FromView
{
    public $meeting_points;
    
    public function __construct($meeting_point)
    {
        $this->meeting_points = $meeting_point;
    }
    
    public function view(): View
    {
        return view('csv.meetingPoint', [
            'meeting_points' => $this->meeting_points
        ]);
    }
}
