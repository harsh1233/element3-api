<?php

namespace App\Models;

use App\User;
use App\Models\Todo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TodoAction extends Model
{
    use SoftDeletes;
    protected $fillable = ['todo_id','action_by','action_type','assigned_to','created_by','updated_by','is_read'];

    public function todo_detail()
    {
        return $this->belongsTo(Todo::class,'todo_id')->withTrashed();
    }
    
    public function action_user_detail()
    {
        return $this->belongsTo(User::class,'action_by');
    }

    public function assignee_detail()
    {
        return $this->belongsTo(User::class,'assigned_to');
    }
}
