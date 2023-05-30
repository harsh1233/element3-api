<?php
namespace App\Exports;

use App\Models\Contact;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class LanguagesExport implements FromView
{
    public $language_data;
    
    public function __construct($language)
    {
        $this->language_data = $language;
    }
    
    public function view(): View
    {
        return view('csv.languageslist', [
            'language_data' => $this->language_data
        ]);
    }
}
