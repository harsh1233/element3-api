<?php

namespace App\Exports;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class CourseExport implements FromView
{
    public $courses_data;
    
    public function __construct($courses) {
        $this->courses_data = $courses;   
    }
    
    public function view(): View
    {
        return view('csv.courses', [
            'courses_data' => $this->courses_data
        ]);
    }
}
