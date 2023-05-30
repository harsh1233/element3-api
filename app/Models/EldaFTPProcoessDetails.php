<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EldaFTPProcoessDetails extends Model
{
        protected $table = 'elda_ftp_process_detail';

       protected $fillable = [
        'process_name',
        'contact_id',
        'elda_insurance_number',
        'elda_insurance_reference_number',
        'gkk_file',
        'ret_file',
        'xml_file',
        'output_txtfile',
        'status',
        'xml_elda_text'
    ];
}
