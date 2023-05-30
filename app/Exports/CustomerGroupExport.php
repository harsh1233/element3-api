<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class CustomerGroupExport implements FromView
{
    public $groups_data;
    
    public function __construct($groups) {
        $this->groups_data = $groups;   
    }
    
    public function view(): View
    {
        return view('csv.customer_group', [
            'groups_data' => $this->groups_data
        ]);
    }
}
