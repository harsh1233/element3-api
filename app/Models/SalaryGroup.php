<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'salary_type',
        'amount',
        'sum_per_extra_hour',
        'paid_sick_leave',
        'paid_vacation_leave',
        'created_by',
        'updated_by'];
}
