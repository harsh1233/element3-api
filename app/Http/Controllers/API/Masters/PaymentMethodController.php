<?php

namespace App\Http\Controllers\API\Masters;

use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\CreditCardMaster;

class PaymentMethodController extends Controller
{
    use Functions;

    /** Update Payment Method test and live secret/access */
    public function updatePaymentMethod(Request $request, $type)
    {
        $v = validator($request->all(), [
            'is_active' => 'boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $PaymentMethod = PaymentMethod::where('type', $type)->first();
        if (!$PaymentMethod) {
            return $this->sendResponse(false, 'Payment Method not found');
        }
        $input = $request->all();
        $PaymentMethod->update($input);
        return $this->sendResponse(true, 'success', $PaymentMethod);
    }

    /** Get Payment Methods  */
    /** Get Payment Methods Listing and return fleg which payment method is added in conatct prefer method */
    public function getPaymentMethod()
    {
        $PaymentMethod = PaymentMethod::query();
        if(!isset($_GET['all'])) {
            $PaymentMethod = $PaymentMethod->whereNotIN('type', ['O','CON']);
        }
        $PaymentMethod = $PaymentMethod->get();
        return $this->sendResponse(true, 'success', $PaymentMethod);
    }

    /**Get credit card types */
    public function getCreditCardTypes()
    {
        $creditCardTypes = CreditCardMaster::get();
        return $this->sendResponse(true, 'success', $creditCardTypes);
    }
}
