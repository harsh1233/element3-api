<?php

namespace App\Http\Controllers\API;

use Excel;
use App\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Exports\ExpenditureExport;
use App\Jobs\SendPushNotification;
use App\Http\Controllers\Functions;
use App\Models\Finance\Expenditure;
use App\Http\Controllers\Controller;
use App\Models\Finance\ExpenditureDetail;

class ExpenditureController extends Controller
{
    use Functions;

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            // Type --- "" - Both, P - product, S - Service
            'type'                    => 'nullable|in:P,S',

            // Search type --- "" - no search, "D" - search description field, "C" - search in check number,
            // Search type --- "R" - search Reference number, "ALL" - search in any of the 3 fields.
            'search_type'             => 'nullable|in:D,C,R,ALL',

            // search text
            'search_text'             => 'nullable|string',

            // Date range filter type --- "" - no date filter, P - search date paid, A - search date added.
            'date_filter_type'         => 'nullable|in:P,A',

            'payment_status'          => 'nullable|in:PP,P,NP',
            'tax_consultation_status' => 'nullable|in:ND,IP,A,R'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $expenditures = Expenditure::latest()->with('user', 'details');
        
        if(empty($request->is_export)){
            if (auth()->user()->type() === 'Instructor') {
                $expenditures->where('user_id', auth()->user()->id);
            }
        }

        // search
        if ($request->search_type && $request->search_text) {
            if ($request->search_type === 'D') {
                $expenditures->where('description', 'like', "%$request->search_text%");
            }

            if ($request->search_type === 'C') {
                $expenditures->where('check_number', 'like', "%$request->search_text%");
            }

            if ($request->search_type === 'R') {
                $expenditures->where('reference_number', 'like', "%$request->search_text%");
            }

            if ($request->search_type === 'ALL') {
                $expenditures->where(function ($query) use ($request) {
                    $query->where('description', 'like', "%$request->search_text%")
                          ->orWhere('check_number', 'like', "%$request->search_text%")
                          ->orWhere('reference_number', 'like', "%$request->search_text%");
                });
            }
        }

        // date range filter
        if ($request->date_filter_type && $request->from_date && $request->to_date) {
            if ($request->date_filter_type === 'P') {
                $expenditures->whereBetween('date_of_expense', [$request->from_date.' 00:00:00', $request->to_date.' 23:59:59']);
            }

            if ($request->date_filter_type === 'A') {
                $expenditures->whereBetween('created_at', [$request->from_date.' 00:00:00', $request->to_date.' 23:59:59']);
            }
        }

        // filter by type
        if ($request->type) {
            if ($request->type === 'P') {
                $expenditures->where('is_product', true);
            }
            if ($request->type === 'S') {
                $expenditures->where('is_service', true);
            }
        }

        // filter by payment status
        if ($request->payment_status) {
            $expenditures->where('payment_status', $request->payment_status);
        }

        // filter by tax consultation status
        if ($request->tax_consultation_status) {
            $expenditures->where('tax_consultation_status', $request->tax_consultation_status);
        }
        $expenditures = $expenditures->get();

        if($request->is_export){
            return Excel::download(new ExpenditureExport($expenditures->toArray()), 'Expenditures.csv');
        }

        return $this->sendResponse(true, 'List of expenditures.', $expenditures);
    }

    public function get($id)
    {
        $expenditure = Expenditure::find($id);
        if (!$expenditure) {
            return $this->sendResponse(false, __('strings.expenditure_not_found'));
        }
        $expenditure->load('user', 'details');
        return $this->sendResponse(true, 'Expenditure details.', $expenditure);
    }

    public function create(Request $request)
    {
        $v = $this->customValidate($request->all());

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $user = User::find($request->user_id);
        if (auth()->user()->type() === 'Instructor') {
            $user = auth()->user();
        }

        $images = $request->receipt_images;
        if ($images) {
            if (!$request->is_app_user) {
                $images_url = [];
                foreach ($images as $image) {
                    $url = $this->uploadImage('invoices', 'receipt', $image);
                    $images_url[] = $url;
                }
                $images = $images_url;
            }
        }

        $expenditure = Expenditure::create($request->only(
            'description',
            'is_product',
            'is_service',
            'check_number',
            'reference_number',
            'date_of_expense',
            'amount',
            'payment_type',
            'payment_status'
        ) + [
            'user_id' => $user->id,
            'receipt_images' => $images,
            'tax_consultation_status' => 'ND',
        ]);

        /**Add crm user action trail */
            if ($expenditure) {
                $action_id = $expenditure->id; //expenditure id
                $action_type = 'A'; //A = Add
                $module_id = 22; //module id base module table
                $module_name = "Expenditure"; //module name base module table
                $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
            }
        /**End manage trail */

        return $this->sendResponse(true, __('strings.expenditure_create_success'), $expenditure);
    }

    public function update(Request $request)
    {
        $v = $this->customValidate($request->all(), 'update');

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $expenditure = Expenditure::find($request->expenditure_id);
        $old_user_id = $expenditure->user_id;

        if (!$expenditure) {
            return $this->sendResponse(false, 'Expenditure not found.');
        }

        if (auth()->user()->type() === 'Instructor' && $expenditure->user_id !== auth()->user()->id) {
            return $this->sendResponse(false, 'You are not allowed to update the expenditure.');
        }

        $user = User::find($request->user_id);
        if (auth()->user()->type() === 'Instructor') {
            $user = auth()->user();
        }

        $images = $request->receipt_images;
        $new_images = $request->new_receipt_images;
        if ($new_images) {
            if (!$request->is_app_user) {
                $new_images_url = [];
                foreach ($new_images as $image) {
                    $url = $this->uploadImage('invoices', 'receipt', $image);
                    $new_images_url[] = $url;
                }
                $new_images = $new_images_url;

                // merge both the exsisting images and new images
                $images = array_merge($images, $new_images);
            }
        }

        $expenditure->update($request->only(
            'description',
            'is_product',
            'is_service',
            'check_number',
            'reference_number',
            'date_of_expense',
            'amount',
            'payment_type',
            'payment_status'
        ) + [
            'user_id' => $user->id,
            'receipt_images' => $images,
            'tax_consultation_status' => auth()->user()->type() === 'Instructor' ? 'ND' : $expenditure->tax_consultation_status,
        ]);

        $detail = new ExpenditureDetail();
        $detail->action = "U";

        $expenditure->details()->save($detail);

        /**Add crm user action trail */
            if ($expenditure) {
                $action_id = $expenditure->id; //expenditure id
                $action_type = 'U'; //U = Updated
                $module_id = 22; //module id base module table
                $module_name = "Expenditure"; //module name base module table
                $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
            }
        /**End manage trail */

        return $this->sendResponse(true, __('strings.expenditure_update_success'), $expenditure);
    }

    public function delete($id, Request $request)
    {
        $v = validator($request->all(), [
            'reason' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $expenditure = Expenditure::find($id);
        if (!$expenditure) {
            return $this->sendResponse(false, __('strings.expenditure_not_found'));
        }

        if (auth()->user()->type() === 'Instructor' && $expenditure->user_id !== auth()->user()->id) {
            return $this->sendResponse(false, 'You are not allowed to update the expenditure.');
        }

        $detail = new ExpenditureDetail();
        $detail->action = "D";
        $detail->rejection_deletion_reason = $request->reason;

        $expenditure->details()->save($detail);

        /**Add crm user action trail */
            if ($expenditure) {
                $action_id = $expenditure->id; //expenditure id
                $action_type = 'D'; //D = Deleted
                $module_id = 22; //module id base module table
                $module_name = "Expenditure"; //module name base module table
                $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
            }
        /**End manage trail */

        $expenditure->delete();
        return $this->sendResponse(true, __('strings.expenditure_delete_success'));
    }

    public function updateStatus($id, Request $request)
    {
        try {
            // call common function to update expenditure status
            $expenditure = $this->updateExpenditureStatus($id, $request->status,$request->reason);

            /**Add crm user action trail */
            if ($expenditure) {
                $action_id = $expenditure->id; //expenditure id
                
                if($request->status=='A')
                $action_type = 'AP'; //AP = Approved
                else if($request->status=='R')
                $action_type = 'R'; //R = Rejected

                $module_id = 22; //module id base module table
                $module_name = "Expenditure"; //module name base module table
                $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
            }
            /**End manage trail */

            return $this->sendResponse(true, __('strings.expenditure_status_changed'), $expenditure);

        } catch (\Exception $e) {
            return $this->sendResponse(false, $e->getMessage());
        }
    }
    
    public function updateMultipleStatus(Request $request)
    {
        $v = validator($request->all(), [
            'ids' => 'required|array',
            'status' => 'required|in:A,R',
            'reason' => 'requiredif:status,R',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        try {
            foreach ($request->ids as $id) {
                // call common function to update expenditure status
                $expenditure = $this->updateExpenditureStatus($id, $request->status,$request->reason);
                 /**Add crm user action trail */
                if ($expenditure) {
                    $action_id = $expenditure->id; //expenditure id
                    
                    if($request->status=='A')
                    $action_type = 'AP'; //AP = Approved
                    else if($request->status=='R')
                    $action_type = 'R'; //R = Rejected

                    $module_id = 22; //module id base module table
                    $module_name = "Expenditure"; //module name base module table
                    $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
                }
                /**End manage trail */
            }
            
            return $this->sendResponse(true, __('strings.expenditure_status_change_success'));
        } catch (\Exception $e) {
            return $this->sendResponse(false, $e->getMessage());
        }
    }

    public function updateMultiplePaymentStatus(Request $request)
    {
        $v = validator($request->all(), [
            'ids' => 'required|array',
            'payment_status' => 'required|in:PP,P,NP',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        try {
            // call common function to update expenditure payment status
            $ids = $request->ids;
            
            $expenditure = Expenditure::whereIn('id',$ids)->update(['payment_status'=>$request->payment_status]);

            return $this->sendResponse(true, 'strings.expenditure_payment_status_change_success');
        } catch (\Exception $e) {
            return $this->sendResponse(false, $e->getMessage());
        }
    }
    
    protected function customValidate(array $inputs, $type = 'create')
    {
        return validator($inputs, [
            'expenditure_id'     => 'update' === $type ? 'required|integer|min:1' : '',
            'description'        => 'required',
            'is_app_user'        => 'required|bool',
            'is_product'         => 'bool',
            'is_service'         => 'bool',
            'date_of_expense'    => 'required|date',
            'receipt_images'     => 'update' === $type ? 'array|present' : 'array',
            'new_receipt_images' => 'array',
            'amount'             => 'required|numeric',
            'payment_type'       => 'required|in:C,CR',
            'payment_status'     => 'required|in:PP,P,NP',
        ]);
    }
}
