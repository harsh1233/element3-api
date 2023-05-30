<?php

namespace App\Http\Controllers\API;

use Excel;
use App\Models\Office;
use App\Exports\CaseExport;
use App\Models\Finance\Cash;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;

class CashController extends Controller
{
    use Functions;

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'date'      => 'required',
            'type'      => 'nullable|in:CHKIN,CHKOUT,CASHOUT,CASHIN',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $office = Office::find($request->office_id);
        if (!$office) {
            return $this->sendResponse(false, 'Office not found.');
        }

        $cash_entries = Cash::where('office_id', $office->id)
            ->where('date_of_entry', $request->date)
            ->with('office', 'contact', 'created_by_user', 'updated_by_user');

        if ($request->type) {
            $cash_entries->where('type', $request->type);
        }

        $cash_entries = $cash_entries->get();

        if ($cash_entries->count()) {
            $day_start_balance = $cash_entries->first()->running_amount - $cash_entries->first()->amount;
        } else {
            $previos_day_ending_balance = CASH::where('office_id', $request->office_id)
                ->where('date_of_entry', '<=', $request->date)
                ->orderBy('date_of_entry', 'DESC')
                ->orderBy('id', 'DESC')
                ->first();

            $day_start_balance = 0;
            if ($previos_day_ending_balance) {
                $day_start_balance = $previos_day_ending_balance->running_amount;
            } elseif ($office->opening_balance >= 0) {
                $day_start_balance = $office->opening_balance;
            }
        }

        if (!empty($_GET['is_export'])) {
            return Excel::download(new CaseExport($cash_entries->toArray()), 'Cash.csv');
        }

        return $this->sendResponse(true, 'List of cash entries.', [
            'day_start_balance' => $day_start_balance,
            'cash_entries' => $cash_entries
            ]);
    }

    public function get($id)
    {
        $cash_entry = Cash::find($id);
        if (!$cash_entry) {
            return $this->sendResponse(false, 'Cash entry not found.');
        }
        $cash_entry->load('office', 'contact');
        return $this->sendResponse(true, 'Cash entry detail.', $cash_entry);
    }

    public function create(Request $request)
    {
        try {
            // call common function to create cash entry
            $cash_entry = $this->createCashEntry($request->all());

            /**Add crm user action trail */
            if ($cash_entry) {
                $action_id = $cash_entry->id; //cash entry id
                $action_type = 'A'; //A = Add
                $module_id = 21; //module id base module table
                $module_name = "Cash Payment"; //module name base module table
                $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
            }
            /**End manage trail */

            return $this->sendResponse(true, "Cash entry recorded successfully.", $cash_entry);
        } catch (\Exception $e) {
            return $this->sendResponse(false, $e->getMessage());
        }
    }

    public function update(Request $request)
    {
        $v = $this->customValidate($request->all(), 'update');

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $cash_entry = Cash::find($request->cash_id);
        if (!$cash_entry) {
            return $this->sendResponse(false, 'Cash entry not found.');
        }

        // cash entry type cannot be changed on update
        if ($cash_entry->type !== $request->type) {
            return $this->sendResponse(false, 'Cash entry type cannot be changed.');
        }

        if ($cash_entry->type === 'CHKIN' || $cash_entry->type === 'CHKOUT') {
            $update_data = $request->only('description', 'amount');
        } elseif ($cash_entry->type === 'CASHOUT' || $cash_entry->type === 'CASHIN') {
            $update_data = $request->only('contact_id', 'description', 'amount');
        }

        $difference_in_amount = $request->amount - $cash_entry->amount;

        // update cash entry
        $cash_entry->update($update_data);

        // update running balance
        if ($cash_entry->type !== 'CASHOUT') {
            $difference_in_amount = $difference_in_amount;
        } else {
            $difference_in_amount = $difference_in_amount * (-1);
        }

        // update all following records OF THAT DAY
        Cash::where('id', '>=', $cash_entry->id)
            ->where('office_id', $cash_entry->office_id)
            ->where('date_of_entry', $cash_entry->date_of_entry)
            ->increment('running_amount', $difference_in_amount);

        // update all following records AFTER THAT DAY
        Cash::where('office_id', $cash_entry->office_id)
            ->where('date_of_entry', '>', $cash_entry->date_of_entry)
            ->increment('running_amount', $difference_in_amount);


        /* DO NOT MAINTAIN Accounting entries for office */
        /* $office = $cash_entry->office;
        $account = $office->getAccount();
        //update account detail
        $account_id = $account->id;
        $event_code = $cash_entry->type;
        $event_id = $cash_entry->id;
        $event_date = $cash_entry->date_of_entry;
        $amount = $cash_entry->amount;
        $account_detail = $cash_entry->account_detail();

        $this->updateAccountDetail($account_id, $event_code, $event_id, $event_date, $amount, $account_detail->id);

        // update balance of user account
        $account->updateBalance($difference_in_amount); */

        /**Add crm user action trail */
            if ($cash_entry) {
                $action_id = $cash_entry->id; //cash entry id
                $action_type = 'U'; //U = Updated
                $module_id = 21; //module id base module table
                $module_name = "Cash Payment"; //module name base module table
                $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
            }
        /**End manage trail */

        return $this->sendResponse(true, 'Cash entry updated successfully.', $cash_entry);
    }

    public function delete($id)
    {
        $cash_entry = Cash::find($id);
        if (!$cash_entry) {
            return $this->sendResponse(false, 'Cash entry not found.');
        }

        $date = $cash_entry->date_of_entry;
        $office_id = $cash_entry->office_id;

        // all entries
        $all_cash_entry = Cash::where('office_id', $office_id)->where('date_of_entry', $date)->get();
        // if cash entry is first check IN of the day then it cannot be deleted.
        if ($all_cash_entry->first()->id == $id) {
            return $this->sendResponse(false, 'First check in of the day cannot be deleted.');
        }

        $cash_entry->delete();

        // update running balance
        if ($cash_entry->type == 'CHKIN' || $cash_entry->type == 'CASHIN') {
            $difference_in_amount = $cash_entry->amount * (-1);
        } else {
            $difference_in_amount = $cash_entry->amount;
        }

        // update all following records
        Cash::where('id', '>=', $cash_entry->id)
            ->where('office_id', $cash_entry->office_id)
            ->where('date_of_entry', $cash_entry->date_of_entry)
            ->increment('running_amount', $difference_in_amount);

        /* DO NOT MAINTAIN Accounting entries for office */
        /* $office = $cash_entry->office;
        $account = $office->getAccount();

        // delete the old account detail
        $account_detail = $cash_entry->account_detail();
        $this->deleteAccountDetail($account_detail->id);

        // update balance of account
        $account->updateBalance($difference_in_amount); */

        /**Add crm user action trail */
            if ($cash_entry) {
                $action_id = $cash_entry->id; //cash entry id
                $action_type = 'D'; //D = Deleted
                $module_id = 21; //module id base module table
                $module_name = "Cash Payment"; //module name base module table
                $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
            }
        /**End manage trail */

        return $this->sendResponse(true, 'Cash entry deleted successfully.');
    }

    public function report(Request $request)
    {
        $v = validator($request->all(), [
            'year'         => 'nullable|integer|min:1970|max:2100',
            'month'        => 'nullable|date_format:m',
            'office_id'     => 'required',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $office = Office::find($request->office_id);
        if (!$office) {
            return $this->sendResponse(false, 'Office not found.');
        }

        if($request->year && $request->month){
            $time = strtotime("$request->year-$request->month-01");
            $first_day = date("Y-m-d", $time);
            $last_day = date("Y-m-t", $time);
        }else{
            if(!$request->start_date && !$request->end_date){
                return $this->sendResponse(false, __('strings.start_end_date_required'));
            }
            $first_day = $request->start_date;
            $last_day = $request->end_date;
        }
        $cash_entries = Cash::where('office_id', $office->id)->where('date_of_entry', '>=', $first_day)
            ->where('date_of_entry', '<=', $last_day)
            ->with('office', 'contact', 'created_by_user', 'updated_by_user')
            ->orderBy('date_of_entry')
            ->get();

        if ($request->is_export) {
            return Excel::download(new CaseExport($cash_entries), 'Cash.csv');
        }

        $day_wise_entries = $cash_entries->groupBy('date_of_entry');

        foreach ($day_wise_entries as $key => $entries) {
            $data = [];
            $data['entries'] = $entries;
            $data['start'] = $entries->first()->running_amount - $entries->first()->amount;
            $data['end'] = $entries->last()->running_amount;
            $day_wise_entries[$key] = $data;
        }

        return $this->sendResponse(true, 'Cash report', $day_wise_entries->sortKeys());
    }

    protected function customValidate(array $inputs, $type = 'create')
    {
        return validator($inputs, [
            'cash_id'       => 'update' === $type ? 'required|integer|min:1' : '',
            'type'          => 'required|in:CHKIN,CHKOUT,CASHOUT,CASHIN',
            'office_id'     => 'create' === $type ? 'required' : '', // office cannot be changed on update
            'description'   => 'required',
            'contact_id'    => 'required_if:type,CASHOUT|required_if:type,CASHIN',
            'date_of_entry' => 'create' === $type ? 'required|date' : '', // date cannot be changed on update
            'amount'        => 'required|numeric'
        ]);
    }
}
