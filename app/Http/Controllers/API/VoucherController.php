<?php

namespace App\Http\Controllers\API;

use Excel;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Exports\VoucherExport;
use App\Models\Courses\Course;
use App\Models\Finance\Voucher;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\BookingProcess\BookingProcessPaymentDetails;
use App\Models\Courses\CourseDetail;

class VoucherController extends Controller
{
    use Functions;

    public function list(Request $request)
    {
        $vouchers = Voucher::with('contact', 'customer', 'course', 'invoice')
        ->orderBy('id', 'desc')
        ->get();

        if (!empty($_GET['is_export'])) {
            return Excel::download(new VoucherExport($vouchers->toArray()), 'Voucher.csv');
        }

        return $this->sendResponse(true, 'List of vouchers.', $vouchers);
    }

    public function get($id)
    {
        $voucher = Voucher::find($id);
        if (!$voucher) {
            return $this->sendResponse(false, __('strings.voucher_not_found'));
        }
        $voucher->load('contact', 'customer', 'course');
        return $this->sendResponse(true, 'Voucher details.', $voucher);
    }

    public function check(Request $request)
    {
        $v = validator($request->all(), [
            'voucher_code'        => 'required|size:15',
            'contact_id'          => 'required|integer|min:1',
            'course_id'           => 'integer|min:1|required_without:course_detail_id',
            'course_detail_id'    => 'integer|min:1',
        ], [
            'course_id.required_without' => 'Either course_id or course_detail_id is required.',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $voucher = Voucher::where('code', $request->voucher_code)->first();
        if (!$voucher) {
            return $this->sendResponse(false, __('strings.voucher_code_invalid'));
        }

        // voucher is already used
        if ($voucher->status === 'U') {
            return $this->sendResponse(false, __('strings.voucher_used_once'));
        }

        //if voucher has contact && voucher has different contact than the booking invoice
        if ($voucher->contact_id) {
            if ($voucher->contact_id != $request->contact_id) {
                return $this->sendResponse(false, __('strings.voucher_not_used_for_user'));
            }
        }

        if ($request->course_id) {
            $course_id = $request->course_id;
        } elseif ($request->course_detail_id) {
            $course_detail = CourseDetail::find($request->course_detail_id);

            if (!$course_detail) {
                return $this->sendResponse(false, __('strings.course_detail_not_found'));
            }
            $course_id = $course_detail->course_id;
        }

        // voucher has different course
        if ($voucher->course_id != $course_id) {
            return $this->sendResponse(false, __('strings.voucher_not_used_for_course'));
        }

        $voucher->load('contact', 'customer', 'course');
        return $this->sendResponse(true, __('strings.voucher_check_success'), $voucher);
    }

    public function create(Request $request)
    {
        $v = $this->customValidate($request->all());

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        // payee check
        if ($request->contact_id) {
            $payee = Contact::find($request->contact_id);
            if (!$payee) {
                return $this->sendResponse(false, __('strings.contact_not_found'));
            }
            if (!$payee->isType('Customer')) {
                return $this->sendResponse(false, __('strings.contact_not_customer'));
            }
        }

        // customer check
        if ($request->customer_id) {
            $customer = Contact::find($request->customer_id);
            if (!$customer) {
                return $this->sendResponse(false, __('strings.contact_not_found'));
            }
            if (!$customer->isType('Customer')) {
                return $this->sendResponse(false, __('strings.contact_not_customer'));
            }
        }

        $course = Course::find($request->course_id);
        if (!$course) {
            return $this->sendResponse(false, __('strings.course_not_found'));
        }

        $voucher = Voucher::create(
            $request->only('contact_id', 'contact_name', 'customer_id', 'customer_name', 'course_id', 'date_of_purchase', 'amount_type', 'amount', 'max_number_times_use') + [
                'code' => substr(md5(uniqid(mt_rand(), true)), 0, 15)
            ]
        );

        /**Add crm user action trail */
                if ($voucher) {
                    $action_id = $voucher->id; //voucher id
                    $action_type = 'A'; //A = Add
                    $module_id = 24; //module id base module table
                    $module_name = "Voucher"; //module name base module table
                    $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
                }
        /**End manage trail */
        return $this->sendResponse(true, 'Voucher created successfully.', $voucher);
    }

    public function update(Request $request)
    {
        $v = $this->customValidate($request->all(), 'update');

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $voucher = Voucher::find($request->voucher_id);
        if (!$voucher) {
            return $this->sendResponse(false, 'Voucher not found.');
        }
        if ($voucher->status === 'U') {
            return $this->sendResponse(false, 'Voucher already used, cannot be updated.');
        }

        // payee check
        if ($request->contact_id) {
            $payee = Contact::find($request->contact_id);
            if (!$payee) {
                return $this->sendResponse(false, 'Contact not found.');
            }
            if (!$payee->isType('Customer')) {
                return $this->sendResponse(false, 'Contact is not customer.');
            }
        }

        // customer check
        if ($request->customer_id) {
            $customer = Contact::find($request->customer_id);
            if (!$customer) {
                return $this->sendResponse(false, 'Contact not found.');
            }
            if (!$customer->isType('Customer')) {
                return $this->sendResponse(false, 'Contact is not customer.');
            }
        }

        $course = Course::find($request->course_id);
        if (!$course) {
            return $this->sendResponse(false, 'Course not found.');
        }

        $voucher->update(
            $request->only('contact_id', 'contact_name', 'customer_id', 'customer_name', 'course_id', 'date_of_purchase', 'amount_type', 'amount', 'max_number_times_use')
        );

        /**Add crm user action trail */
                if ($voucher) {
                    $action_id = $voucher->id; //voucher id
                    $action_type = 'U'; //U = Updated
                    $module_id = 24; //module id base module table
                    $module_name = "Voucher"; //module name base module table
                    $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
                }
        /**End manage trail */

        return $this->sendResponse(true, 'Voucher updated successfully.', $voucher);
    }

    public function delete($id)
    {
        $voucher = Voucher::find($id);
        if (!$voucher) {
            return $this->sendResponse(false, 'Voucher not found.');
        }

        /**Add crm user action trail */
                if ($voucher) {
                    $action_id = $voucher->id; //voucher id
                    $action_type = 'D'; //D = Deleted
                    $module_id = 24; //module id base module table
                    $module_name = "Voucher"; //module name base module table
                    $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
                }
        /**End manage trail */

        $voucher->delete();

        return $this->sendResponse(true, 'Voucher deleted successfully.');
    }

    /* DEPRECATED - no one can apply voucher via api */
    public function apply(Request $request)
    {
        $v = validator($request->all(), [
            'voucher_code' => 'required|size:15',
            'invoice_id' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $voucher = Voucher::where('code', $request->voucher_code)->first();
        if (!$voucher) {
            return $this->sendResponse(false, 'Voucher code is invalid.');
        }

        try {
            $invoice = $voucher->apply($request->invoice_id);

            return $this->sendResponse(true, 'Voucher applied successfully.', [
                'invoice' => $invoice,
            ]);
        } catch (\Exception $e) {
            return $this->sendResponse(false, $e->getMessage());
        }
    }

    protected function customValidate(array $inputs, $type = 'create')
    {
        $max_validation_for_amount = '';
        if (isset($inputs['amount_type'])) {
            $max_validation_for_amount = $inputs['amount_type'] === 'P' ? '|max:100' : '';
        }

        return validator($inputs, [
            'voucher_id'       => 'update' === $type ? 'required|integer|min:1' : '',
            'contact_id'       => 'nullable|integer|min:1',
            'contact_name'     => 'max:50',
            'customer_id'      => 'nullable|integer|min:1',
            'customer_name'    => 'max:50',
            'course_id'        => 'required|integer|min:1',
            'amount_type'      => 'required|in:P,V',
            'amount'           => 'required|numeric|min:1'.$max_validation_for_amount,
            'date_of_purchase' => 'required|date',
            'max_number_times_use' => 'nullable'
        ]);
    }
}
