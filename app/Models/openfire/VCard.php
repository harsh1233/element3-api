<?php

namespace App\Models\openfire;

use Illuminate\Database\Eloquent\Model;

class VCard extends Model
{
	
    protected $table="ofVCard";

    protected $fillable = ['username','vcard'
     ];

    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;
    protected $connection= 'mysql_external'; 

   
}
