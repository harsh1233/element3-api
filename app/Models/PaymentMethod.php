<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    public $timestamps = false;
    protected $fillable = ['type','is_active','test_access','live_access'];

    protected $appends = ['prefer_payment_method_status'];

    public function getPreferPaymentMethodStatusAttribute()
    {
        $contact_id = null;
        if(auth()->user()) {
            $contact_id = auth()->user()->contact_id;    
        }
        if(isset($_GET['contact_id'])) {
            $contact_id = $_GET['contact_id'];    
        }
        
        if(!$contact_id){return 'False';};

        $contact = Contact::find($contact_id);
        //dd($contact->prefer_payment_method_id);
        if($contact){
            $payment_method_id = $this->id;
            $current_date = date('Y-m-d H:i:s');
                if($payment_method_id == $contact->prefer_payment_method_id){
                    return 'True';
                }else{
                    return 'False';
                }
        }
    }
}
