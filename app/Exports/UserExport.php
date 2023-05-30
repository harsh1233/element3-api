<?php

namespace App\Exports;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class UserExport implements FromView
{
    public $users_data;
    
    public function __construct($users) {
        $this->users_data = $users;   
    }
    
    public function view(): View
    {
        return view('csv.users', [
            'user_data' => $this->users_data
        ]);
    }
}
