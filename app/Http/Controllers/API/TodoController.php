<?php

namespace App\Http\Controllers\API;

use App\User;
use App\Models\Todo;
use App\Models\TodoAction;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;

class TodoController extends Controller
{
    use Functions;

    public function getAdmins()
    {
        $admins = User::select('id','name','email','role')->whereHas('role_detail', function($q){
            $q->where('code','A');
            $q->orWhere('code','SA');
        })->get();

        return $this->sendResponse(true,'success',$admins);
    }

    public function createTodo(Request $request)
    {
        $v = validator($request->all(), [
            'title' => 'required|max:250',
            'description' => 'required',
            'due_date' => 'required|date_format:Y-m-d H:i:s',
            'assigned_to' => 'nullable|integer',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $input = $request->all();
        $input['created_by'] = auth()->user()->id;
        if(!$request->assigned_to) {
            $input['assigned_to'] = auth()->user()->id;
        }

        $todo = Todo::create($input);

        //create todo action
        $this->createAction($todo->id,'created');

        return $this->sendResponse(true,__('strings.todo_create_success'),$todo);
    }
    
    public function updateTodo(Request $request,$id)
    {
        $v = validator($request->all(), [
            'title' => 'required|max:250',
            'description' => 'required',
            'due_date' => 'required|date_format:Y-m-d H:i:s',
            'assigned_to' => 'nullable|integer',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        $todo = Todo::find($id);
        if (!$todo) return $this->sendResponse(false,'Todo not found');
        $input = $request->all();
        $input['updated_by'] = auth()->user()->id;
        if(!$request->assigned_to) {
            $input['assigned_to'] = auth()->user()->id;
        }

        $todo->update($input);

        //create todo action
        $this->createAction($id,'updated');

        return $this->sendResponse(true,__('strings.todo_update_success'),$todo);
    }

    public function deleteTodo($id)
    {
        $todo = Todo::find($id);
        if (!$todo) return $this->sendResponse(false,'Todo not found');

        $todo->delete();

        //create todo action
        $this->createAction($id,'deleted');

        return $this->sendResponse(true,__('strings.todo_delete_success'),$todo);
    }
    
    public function assignToAdmin(Request $request,$id)
    {
        $v = validator($request->all(), [
            'assigned_to' => 'required|integer',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $todo = Todo::find($id);
        if (!$todo) return $this->sendResponse(false,'Todo not found');
        $todo->assigned_to = $request->assigned_to;
        $todo->save();

        //create todo action
        $this->createAction($id,'assigned',$request->assigned_to);

        return $this->sendResponse(true,__('strings.todo_update_success'),$todo);
    }

    public function markAsDone($id)
    {
        $todo = Todo::find($id);
        if (!$todo) return $this->sendResponse(false,'Todo not found');
        $todo->is_done = true;
        $todo->done_by = auth()->user()->id;
        $todo->save();
        //create todo action
        $this->createAction($id,'done');
        return $this->sendResponse(true,__('strings.todo_update_success'),$todo);
    }

    public function listTodo(Request $request)
    {
        $todos = Todo::
        with(['assignee_detail'=>function($query){
            $query->select('id','name','email');
        }])
        ->with(['done_user_detail'=>function($query){
            $query->select('id','name','email');
        }]);

        $count = $todos->count();

        if($request->search) {
            $search = $request->search;
            $todos = $todos->where(function($query) use($search){
                $query->where('title','like',"%$search%");
                $query->orWhere('description','like',"%$search%");
            });
            $count = $todos->count();
        }
        if($request->page && $request->perPage) {
            $page = $request->page;
            $perPage = $request->perPage;
            $todos = $todos->skip($perPage*($page-1))->take($perPage);
        }
        $todos = $todos->orderBy('due_date','asc')->get();
        $data = [
            'todos' => $todos,
            'count' => $count
        ];
        return $this->sendResponse(true,__('Todo list'),$data);
    }

    public function createAction($todo_id,$action_type,$assigned_to=null)
    {
        $action = [];
        $action['todo_id'] = $todo_id;
        $action['action_by'] = auth()->user()->id;
        $action['created_by'] = auth()->user()->id;
        $action['action_type'] = $action_type;
        $action['assigned_to'] = $assigned_to;
        TodoAction::create($action);
    }

    public function listTodoAction(Request $request)
    {
        $todos = TodoAction::
        with('todo_detail')
        ->with(['action_user_detail'=>function($query){
            $query->select('id','name','email');
        }])
        ->with(['assignee_detail'=>function($query){
            $query->select('id','name','email');
        }]);

        $unreadCount = TodoAction::where('is_read',false)->count();

        if($request->page && $request->perPage) {
            $page = $request->page;
            $perPage = $request->perPage;
            $todos = $todos->skip($perPage*($page-1))->take($perPage);
        }
        //$todos = $todos->get()->sortBy('todo_detail.due_date')->toArray();
        $todos = $todos->orderBy('id','desc')->get();

        $data = [
            'todos' => $todos,
            'unreadCount' => $unreadCount
        ];

        return $this->sendResponse(true,__('Todo Action list'),$data);
    }

    public function deleteTodoAction(Request $request)
    {
        $v = validator($request->all(), [
            'ids' => 'required|array',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        $todo = TodoAction::whereIn('id',$request->ids);
        if (!$todo->count()) return $this->sendResponse(false,'Todo action not found');
        $todo->delete();
        return $this->sendResponse(true,__('success'));
    }

    public function readTodoAction(Request $request)
    {
        $v = validator($request->all(), [
            'ids' => 'required|array',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        $todo = TodoAction::whereIn('id',$request->ids);
        if (!$todo->count()) return $this->sendResponse(false,'Todo action not found');
        $todo->update(['is_read'=>true]);
        return $this->sendResponse(true,__('success'));
    }

}
