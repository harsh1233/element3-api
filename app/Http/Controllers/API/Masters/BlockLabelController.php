<?php

namespace App\Http\Controllers\API\Masters;

use App\Models\BlockLabel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;

class BlockLabelController extends Controller
{
    use Functions;

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required|max:50|unique:block_labels',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $block_label = BlockLabel::create($request->only('name'));
        return $this->sendResponse(true, __('strings.create_sucess', ['name' => 'Block label']), $block_label);
    }

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $block_labels = BlockLabel::query();

        if($request->search){
            $search = $request->search;
            $block_labels = $block_labels->where('name', 'like', "%$search%");
        }
        $count = $block_labels->count();
        
        if($request->page && $request->perPage){
            $page = $request->page;
            $perPage = $request->perPage;

            $block_labels->skip($perPage*($page-1))->take($perPage);
        }

        $block_labels = $block_labels->get();
        
        $data = [
            'block_labels' => $block_labels,
            'count' => $count
        ];
        return $this->sendResponse(true, __('strings.list_message', ['name' => 'Block label']), $data);
    }

    public function update(Request $request, $id)
    {
        $v = validator($request->all(), [
            'name' => 'required|max:50|unique:block_labels',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $block_label = BlockLabel::find($id);

        if(!$block_label)return $this->sendResponse(false, __('strings.not_found_validation', ['name' => 'Block label']));

        $block_label->name = $request->name;
        $block_label->save();

        return $this->sendResponse(true, __('strings.update_sucess', ['name' => 'Block label']), $block_label);
    }

    public function view($id)
    {
        $block_label = BlockLabel::find($id);

        if(!$block_label)return $this->sendResponse(false, __('strings.not_found_validation', ['name' => 'Block label']));

        return $this->sendResponse(true, __('strings.get_message', ['name' => 'Block label']), $block_label);
    }

    public function delete($id)
    {
        $block_label = BlockLabel::find($id);

        if(!$block_label)return $this->sendResponse(false, __('strings.not_found_validation', ['name' => 'Block label']));

        $block_label->delete();
        return $this->sendResponse(true, __('strings.delete_sucess', ['name' => 'Block label']));
    }
}
