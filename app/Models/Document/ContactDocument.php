<?php

namespace App\Models\Document;

use App\Models\Contact;
use App\Models\Document\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactDocument extends Model
{
    use SoftDeletes;
    protected $fillable = ['contact_id','document_id','url'];

    public function contact_detail()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function document_detail()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }
}