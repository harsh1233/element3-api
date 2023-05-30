<?php

namespace App\Http\Controllers\API;

use App\User;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Models\Document\Document;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Models\Document\ContactDocument;
use App\Models\InstructorActivity\InstructorActivityTimesheet;

class SignatureController extends Controller
{
    use Functions;

    /**
     * Upload signature for employee/instructor
     * @param  integer $contact_id ID from contacts table
     * @param  string  $signature  AWS Private URL
     * @return object  success message with data
     */
    public function uploadSignature(Request $request)
    {
        $v = validator($request->all(), [
            'contact_id' => 'required|exists:contacts,id',
            'signature' => 'required',
        ]);
        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $contact = Contact::find($request->contact_id);
        $contact->signature = $request->signature;
        $contact->save();

        return $this->sendResponse(true,__('Signature uploaded successfully'),$contact);
    }

    /**
     * Get signature list for employee/instructor
     * @param  integer $page page number
     * @param  integer $perPage per page records
     * @param  string  $search  Search list with name,email,mobile
     * @return object  success message with data
     */
    public function getSignatureList(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'required|integer|min:1',
            'perPage' => 'required|integer|min:1',
            'search' => 'nullable'
        ]);
        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $signatures = Contact::where(function($q){
            $q->where('category_id',2)->orWhere('category_id',3);
        });

        //$signatures = Contact::where('category_id',2);

        if($request->search) {
            $search = $request->search;
            $signatures = $signatures->where(function($query) use($search){
                $query->where('email','like',"%$search%");
                $query->orWhere('mobile1','like',"%$search%");
                $query->orWhere('salutation','like',"%$search%");
                $query->orWhere('first_name','like',"%$search%");
                $query->orWhere('middle_name','like',"%$search%");
                $query->orWhere('last_name','like',"%$search%");
            });
        }
       
        $count = $signatures->count();
        if($request->page && $request->perPage) {
            $page = $request->page;
            $perPage = $request->perPage;
            $signatures = $signatures->skip($perPage*($page-1))->take($perPage);
        }
        $signatures = $signatures->with('category_detail')->with('difficulty_level_detail.difficulty_level_detail')->get();

        $data = [
            'signatures' => $signatures,
            'count' => $count
        ];
        return $this->sendResponse(true,__('Signature list'),$data);
    }


    /**
     * Get signature for given contact(employee/instructor)
     * @param  integer $contact_id ID from contacts table
     * @return object  success message with data
     */
    public function getSignature(Request $request)
    {
        $v = validator($request->all(), [
            'contact_id' => 'required|exists:contacts,id',
        ]);
        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $contact = Contact::find($request->contact_id);
        $url = $contact->signature ? $this->get_private_document($contact->signature) : null;

        return $this->sendResponse(true,__('Signature url'),$url);
    }

    /**
     * Get timesheet signature for given timesheet
     * @param  integer $timesheet_id ID from instructor_activity_timesheets table
     * @return object  success message with data
     */
    public function getTimesheetSignature(Request $request)
    {
        $v = validator($request->all(), [
            'timesheet_id' => 'required|exists:instructor_activity_timesheets,id',
        ]);
        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $timesheet = InstructorActivityTimesheet::find($request->timesheet_id);
        $url = $timesheet->signature ? $this->get_private_document($timesheet->signature) : null;

        return $this->sendResponse(true,__('Timesheet Signature url'),$url);
    }

    /**
     * Get all master document name
     * @return object  success message with data
     */
    public function getDocuments()
    {
        $documents = Document::get();

        return $this->sendResponse(true,__('Document list'),$documents);
    }

    /**
     * Upload document(nationality,passport,driving licence etc) for given contact(employee/instructor)
     * @param  integer $contact_id ID from contacts table
     * @param  integer $document_id ID from documents table
     * @param  integer $attachment File attachment
     * @return object  success message with data
     */
    public function uploadContactDocument(Request $request)
    {
        if($request->contact_id){
            $v = validator($request->all(), [
                'contact_id' => 'nullable|exists:contacts,id',
                'document_id' => 'required|exists:documents,id',
                'attachment' => 'required|file|mimes:jpeg,bmp,png,doc,pdf,docx',
            ]);
            if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
            $contact_id = $request->contact_id;
            $document_id = $request->document_id;
            $attachment = $request->attachment;
            $url = $this->upload_private_document('documents',$attachment);
        }else{
            $v = validator($request->all(), [
                'contact_id' => 'nullable|exists:contacts,id',
                'document_id' => 'required|exists:documents,id',
                'attachment' => 'required',
            ]);
            if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
            $contact_id = auth()->user()->contact_id;
            $document_id = $request->document_id;
            $url = $request->attachment;
        }

        $document = ContactDocument::updateOrCreate(
            ['contact_id' => $contact_id, 'document_id' => $document_id],
            ['contact_id' => $contact_id, 'document_id' => $document_id, 'url' => $url]
        );

        return $this->sendResponse(true,__('Document uploaded successfully'),$document);
    }

    /**
     * Get all document(nationality,passport,driving licence etc) for given contact(employee/instructor)
     * @param  integer $contact_id ID from contacts table
     * @return object  success message with data
     */
    public function getContactDocuments(Request $request)
    {
        $v = validator($request->all(), [
            'contact_id' => 'nullable|exists:contacts,id',
        ]);
        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        if($request->contact_id){
            $contact_id = $request->contact_id;
        }else{
            $contact_id = auth()->user()->contact_id;
        }
        $documents = ContactDocument::where('contact_id',$contact_id)->with('document_detail')->get();

        $documents->map(function($document) {
            $document['url'] = $this->get_private_document($document['url']);
        });

        return $this->sendResponse(true,__('Contact documents'),$documents);
    }
    /**
     * Remove document(nationality,passport,driving licence etc) for given contact(employee/instructor)
     * @param  integer $contact_id ID from contacts table
     * @param  integer $document_id ID from documents table
     * @return object  success message
     */
    public function removeContactDocument(Request $request)
    {
        $v = validator($request->all(), [
            'contact_id' => 'nullable|exists:contacts,id',
            'document_id' => 'required|exists:documents,id',
        ]);
        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        if($request->contact_id){
            $contact_id = $request->contact_id;
        }else{
            $contact_id = auth()->user()->contact_id;
        }

        $document_id = $request->document_id;
 
        $document = ContactDocument::where('contact_id',$contact_id)->where('document_id',$document_id)->first();

        if(!$document) return $this->sendResponse(false,__('Invalid document'));
        $fileUrl = $document->url;
        Storage::disk('s3Private')->delete($fileUrl);
        $document->delete();
        return $this->sendResponse(true,__('Document deleted successfully'));
    }

}
