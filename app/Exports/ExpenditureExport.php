<?php

namespace App\Exports;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ExpenditureExport implements FromView
{
    public $expenditure_data;
    
    public function __construct($expenditures) {
        $this->expenditure_data = $expenditures;   
    }
    
    public function view(): View
    {
        return view('csv.expenditures', [
            'expenditure_data' => $this->expenditure_data
        ]);
    }
}
