<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationType extends Model
{
    protected $table = 'notification_type_mst'; 
    protected $fillable = ['description'];
}
