<?php

namespace App\Exports;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class EldaDetailsExport implements FromView
{
    public $elda;
    
    public function __construct($elda_details) {
        $this->elda = $elda_details;   
    }
    
    public function view(): View
    {
        return view('xls.elda', [
            'elda' => $this->elda
        ]);
    }
}
