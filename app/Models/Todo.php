<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Todo extends Model
{
    use SoftDeletes;
    protected $fillable = ['title','description','is_done','assigned_to','done_by','created_by','updated_by','due_date'];

    public function assignee_detail()
    {
        return $this->belongsTo(User::class,'assigned_to');
    }

    public function done_user_detail()
    {
        return $this->belongsTo(User::class,'done_by');
    }
}
