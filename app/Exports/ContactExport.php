<?php
namespace App\Exports;

use App\Models\Contact;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ContactExport implements FromView
{
    public function view(): View
    {

        $contacts = Contact::query();
        if(isset($_GET['is_employee'])) {
            $contacts = $contacts->where(function($q){
                $q->where('category_id',2)->orWhere('category_id',3);
            });
        }
        if(isset($_GET['category_id']) && $_GET['category_id']!='' && $_GET['category_id']!='null') {
            $contacts = $contacts->where('category_id',$_GET['category_id']);
        }

        if(isset($_GET['search']) && $_GET['search']!='null') {
            $search = $_GET['search'];
            $contacts = $contacts->where(function($query) use($search){
                $query->where('email','like',"%$search%");
                $query->orWhere('mobile1','like',"%$search%");
                $query->orWhere('salutation','like',"%$search%");
                $query->orWhere('first_name','like',"%$search%");
                $query->orWhere('middle_name','like',"%$search%");
                $query->orWhere('last_name','like',"%$search%");
            });
        }
        
        $contacts = $contacts->orderBy('id','desc')->get();
        return view('csv.contacts', [
            'contacts' => $contacts
        ]);
    }
}