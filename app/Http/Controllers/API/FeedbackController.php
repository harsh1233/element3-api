<?php

namespace App\Http\Controllers\API;

use App\User;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Models\Feedback\Feedback;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Feedback\FeedbackDetail;
use App\Models\Feedback\FeedbackQuestion;
use App\Models\BookingProcess\BookingProcessCourseDetails;
use App\Models\BookingProcess\BookingProcessCustomerDetails;
use App\Models\BookingProcess\BookingProcessInstructorDetails;

class FeedbackController extends Controller
{
    use Functions;

    /* Feedback questions creation (For backup purpose only) */
    public function questionCreate()
    {
        $v = validator($request->all(), [
            'question' => 'required|max:512',
            'question_de' => 'max:512',
            'is_under_eighteen' => 'boolean',
            'is_element3' => 'boolean',
         ]);

       if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
       /*  $data = [
            [
                'question' => 'Child-Friendly',
                'question_de' => 'Kinderfreundlich',
                'is_under_eighteen' => true,
                'is_element3' => false
            ],
            [
                'question' => 'Punctuality',
                'question_de' => 'Pünktlichkeit',
                'is_under_eighteen' => false,
                'is_element3' => false
            ],
            [
                'question' => 'Linguistic Proficiency',
                'question_de' => 'Sprachkenntnisse',
                'is_under_eighteen' => false,
                'is_element3' => false
            ],
            [
                'question' => 'Trustworthy',
                'question_de' => 'Vertrauenswürdigkeit',
                'is_under_eighteen' => false,
                'is_element3' => false
            ],
            [
                'question' => 'Recognizable progress',
                'question_de' => 'Fortschritte',
                'is_under_eighteen' => false,
                'is_element3' => false
            ],
            [
                'question' => 'Valuation of the training materials given-- Goal Achieved',
                'question_de' => 'Bewertung der aufgegebene Trainingsmaterialen-- Ziel erreicht',
                'is_under_eighteen' => false,
                'is_element3' => false
            ],
            [
                'question' => 'Entertainment and Conversation',
                'question_de' => 'Entertainment und Konversation',
                'is_under_eighteen' => false,
                'is_element3' => false
            ],
            [
                'question' => 'Manners',
                'question_de' => 'Benehmen',
                'is_under_eighteen' => false,
                'is_element3' => false
            ],
            [
                'question' => 'Element3 in General',
                'question_de' => 'Element3 in General',
                'is_under_eighteen' => false,
                'is_element3' => true
            ],
        ]; */
        FeedbackQuestion::create($request->all());
    }

    /* Get all feedback questions */
    public function getQuestions()
    {
       $questions =  FeedbackQuestion::get();
       return $this->sendResponse(true,__('Feedback questions'),$questions);
    }

    /* Create feedback question by customer */
    public function createFeedback(Request $request)
    {
        $v = validator($request->all(), [
            'booking_id' => 'required|integer',
            'feedback_detail' => 'required|array',
            'feedback_detail.*.rating' => 'required|numeric|max:5|min:0',
            'feedback_detail.*.question_id' => 'required|integer',
            'average_rating' => 'required|numeric|max:5|min:0',
            'final_comment' => 'max:500',
         ]);

       if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

       $booking_id = $request->booking_id;
       $average_rating = $request->average_rating;
       $final_comment = $request->final_comment;
       $feedback_detail = $request->feedback_detail;
       $user = auth()->user();
       $current_datetime = date('Y-m-d H:i:s');
       
       //Check if record already exists
       $checkFeedback = Feedback::where('customer_id',$user->id)->where('booking_id',$booking_id)->count();
       if($checkFeedback) return $this->sendResponse(false,__('You\'ve already submitted feedback for given course'));

       //Check user type
       if($user->type() !== 'Customer') return $this->sendResponse(false,__('Only customer can give feedback'));
       
       //Check course
       $course_detail = BookingProcessCourseDetails::select('id','course_id','booking_process_id')->where('booking_process_id',$booking_id)->first();
       $customer_detail = BookingProcessCustomerDetails::select('id','customer_id','booking_process_id','start_date')->where('booking_process_id',$booking_id)->where('customer_id',$user->contact_id)->first();
       if(!$course_detail || !$customer_detail) return $this->sendResponse(false,__('Course not found'));
       
       //Check instructor
       $instructor_contact_ids = BookingProcessInstructorDetails::where('booking_process_id',$booking_id)->pluck('contact_id');
       $instructors = User::select('id','contact_id')->whereIn('contact_id',$instructor_contact_ids)->get();
       if(count($instructors) == 0) return $this->sendResponse(false,__('Instructor not found'));
       
       //Check booking is completed or not
       $checkBooking = BookingProcessCustomerDetails::where('EndDate_Time','>',$current_datetime)->where('booking_process_id',$booking_id)->where('customer_id',$user->contact_id)->count();
       if($checkBooking) return $this->sendResponse(false,__('strings.feedback_only_for_completed_course'));
       
       //check average rating
       $total_rating =  array_sum(array_column($feedback_detail, 'rating'));
       if(!$total_rating)  return $this->sendResponse(false,__('Please add rating'));
       $calculated_average_rating = $total_rating / count($feedback_detail);
       $calculated_average_rating = number_format($calculated_average_rating,1);
       if($calculated_average_rating != $average_rating) return $this->sendResponse(false,__('Average rating is incorrect'));
            
       $course_id = $course_detail['course_id'];
       $course_taken_date = $customer_detail['start_date'];
       $feedback_id = null;
       foreach ($instructors as $instructor) {
            $feedbackData = [
                'instructor_id'=> $instructor['id'],
                'customer_id' => $user->id,
                'booking_id' => $booking_id,
                'course_id' => $course_id,
                'course_taken_date' => $course_taken_date,
                'average_rating' => $average_rating,
                'final_comment' => $final_comment,
                'created_by' => $user->id
            ];
            $feedback = Feedback::create($feedbackData);
            $feedback_id = $feedback['id'];
            $detailData = [];
            foreach($feedback_detail as $fd) {
                $detailData[] = [
                    'feedback_id' => $feedback['id'],
                    'question_id' => $fd['question_id'],
                    'rating' => $fd['rating'],
                    'created_by' => $user->id,
                    'created_at' => $current_datetime,
                    'updated_at' => $current_datetime,
                ];
            }
            FeedbackDetail::insert($detailData);
            
            //Update total feedback and average rating
            $contact =  Contact::find($instructor['contact_id']);
            if($contact) {
                $total_feedback = $contact->total_feedback + 1;
                $contact->total_feedback = $total_feedback;
                $contact->average_rating = ($contact->average_rating+$average_rating)/$total_feedback;
                $contact->save();
            }
            
       }
       $data = ['feedback_id'=>$feedback_id];
       return $this->sendResponse(true,__('strings.feedback_created_success'),$data);
    }

    /* View feedback detail for customer,instructor and admin */
    public function viewFeedback($feedback_id)
    {
        $feedback = Feedback::with(['feedback_detail'=>function($q){
            $q->select('id','feedback_id','question_id','rating');
            $q->with(['feedback_question'=>function($q2){
                $q2->select('id','question','question_de');
                }]);
            }])
            ->with(['course_detail'=>function($q){
                $q->select('id','name','type','course_banner');
             }])
            ->find($feedback_id);
        if(!$feedback) return $this->sendResponse(true,__('Feedback not exist'));
        return $this->sendResponse(true,__('Feedback detail'),$feedback);
    }

    /* List feedback for customer,instructor and admin */
    public function listFeedback(Request $request)
    {
        $v = validator($request->all(), [
            'instructor_id' => 'nullable|integer',
            'course_id' => 'nullable|integer',
            'date' => 'nullable|date_format:Y-m-d',
            'booking_id' => 'nullable|integer',
         ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $feedbacks = Feedback::select('id','instructor_id','customer_id','booking_id','course_id','course_taken_date','average_rating','final_comment','created_at');

        //Check for instructor
        if((auth()->user()->type() === 'Instructor') || ($request->instructor_id)) {
           if((auth()->user()->type() === 'Instructor')) {
               $instructor_id  = auth()->user()->id;
           } else {
               $user = User::where('contact_id',$request->instructor_id)->first();
               if(!$user) return $this->sendResponse(false,"Please enter valid instructor id");
               $instructor_id = $user->id;
           }
            $feedbacks = $feedbacks->where('instructor_id',$instructor_id);
        }

        //Check for customer
        if((auth()->user()->type() === 'Customer')) {
            $customer_id  = auth()->user()->id;
            $feedbacks = $feedbacks->where('customer_id',$customer_id);
        }

        //Check for booking
        if($request->booking_id) {
            $booking_id  = $request->booking_id;
            $feedbacks = $feedbacks->where('booking_id',$booking_id);
        }

        //Check for course filter
        if($request->course_id) {
            $feedbacks = $feedbacks->where('course_id',$request->course_id);
        }

        //Check for date filter
        if($request->date) {
            $feedbacks = $feedbacks->whereDate('created_at',$request->date);
        }

        $totalCount = $feedbacks->count();

        //Pagination
        if($request->perPage && $request->page) {
            $perPage = $request->perPage;
            $page = $request->page;
            $feedbacks = $feedbacks->skip($perPage*($page-1))->take($perPage);
        }

        $feedbacks = $feedbacks
        ->with(['customer_detail'=>function($q){
            $q->select('id','name','contact_id','email');
            $q->with(['contact_detail'=>function($q2){
                $q2->select('id','profile_pic');
                }]);
            }])
        ->with(['instructor_detail'=>function($q){
            $q->select('id','name','contact_id','email');
            }])
        ->with(['feedback_detail'=>function($q){
            $q->select('id','feedback_id','question_id','rating');
            $q->with(['feedback_question'=>function($q2){
                $q2->select('id','question','question_de','is_under_eighteen','is_element3');
                }]);
            }])
         ->with(['course_detail'=>function($q){
                $q->select('id','name','type','course_banner');
             }])
        ->latest()->get();
        $data = ['feedbacks'=>$feedbacks,'count'=>$totalCount];
        return $this->sendResponse(true,__('Feedback List'),$data);
    }

     /* Delete feedback and feedback detail */
     public function deleteFeedback($feedback_id)
     {
         $feedback = Feedback::find($feedback_id);
         if(!$feedback) return $this->sendResponse(true,__('Feedback not exist'));
         $feedback->delete();
        return $this->sendResponse(true,__('Feedback deleted successfully'));
     }
}
