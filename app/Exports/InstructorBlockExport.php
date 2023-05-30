<?php
namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class InstructorBlockExport implements FromView
{
    public $instructor_blocks_data;
    
    public function __construct($instructor_blocks) {
        $this->instructor_blocks_data = $instructor_blocks;
    }
    
    public function view(): View
    {
        return view('csv.instructor_blocks', [
            'instructor_blocks_data' => $this->instructor_blocks_data
        ]);
    }
}