<?php

namespace App\Http\Controllers\API;
use Excel;
use App\Models\Group;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Models\CustomerGroup;
use App\Http\Controllers\Functions;
use App\Exports\CustomerGroupExport;
use App\Http\Controllers\Controller;

class CustomerGroupController extends Controller
{
    use Functions;

    /* create customer group */
    public function createGroup(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required|max:50',
            'description' => 'required|max:500',
            'customers' => 'array'
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $input = $request->all();
        $input['created_by'] = auth()->user()->id;
        $group = Group::create($input);

        if($request->customers) {
            $customers = array();
            $courseDetail = array_map(function($customer_id) use ($group, $customers) {
                $customers['contact_id'] = $customer_id;
                $customers['group_id'] = $group->id;
                $customers['added_by'] = auth()->user()->id;
                $customers['created_at'] = date("Y-m-d H:i:s");
                return $customers;
            }, $request['customers']);
            CustomerGroup::insert($courseDetail);
        }
        return $this->sendResponse(true,__('strings.group_created_success'));
    }

    /* update customer group */
    public function updateGroup(Request $request,$id)
    {
        $v = validator($request->all(), [
            'name' => 'required|max:50',
            'description' => 'required|max:500',
            'customers' => 'array'
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        
        $group = Group::find($id);
        if (!$group) return $this->sendResponse(false,'Group not found');
        
        $group->name = $request->name;
        $group->description = $request->description;
        $group->updated_by = auth()->user()->id;
        $group->save();

        if($request->customers) {
            CustomerGroup::where('group_id',$id)->delete();
            $customers = array();
            $courseDetail = array_map(function($customer_id) use ($group, $customers) {
                $customers['contact_id'] = $customer_id;
                $customers['group_id'] = $group->id;
                $customers['added_by'] = auth()->user()->id;
                $customers['created_at'] = date("Y-m-d H:i:s");
                $customers['updated_at'] = date("Y-m-d H:i:s");
                return $customers;
            }, $request['customers']);
            CustomerGroup::insert($courseDetail);
        }

        return $this->sendResponse(true,__('strings.group_updated_success'));
    }

    /* delete customer group */
    public function deleteGroup(Request $request,$id)
    {
        $group = Group::find($id);
        if (!$group) return $this->sendResponse(false,'Group not found');
        if($group->is_running) return $this->sendResponse(false,__('strings.running_group_delete'));
        $group->delete();
        return $this->sendResponse(true,__('strings.group_deleted_success'));
    }

    /* list customer group */
    public function listGroup(Request $request)
    {
        $groups = Group::with('customers.customer_detail')->latest()->get();

        if(!empty($_GET['is_export'])){
            return Excel::download(new CustomerGroupExport($groups->toArray()), 'CustomerGroup.csv');  
        }

        return $this->sendResponse(true,'success',$groups);
    }

    /* search customers */
    public function getCustomers()
    {
        $customers = Contact::where('category_id',1)->get();
        return $this->sendResponse(true,'success',$customers);
    }

    /* get customer group */
    public function getGroup($id)
    {
        $group = Group::with('customers.customer_detail')->find($id);
        if (!$group) return $this->sendResponse(false,'Group not found');
        return $this->sendResponse(true,'success',$group);
    }
}
