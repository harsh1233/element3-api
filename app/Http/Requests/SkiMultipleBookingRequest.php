<?php

namespace App\Http\Requests;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SkiMultipleBookingRequest extends FormRequest
{
    use Functions;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'booking_details' => 'required|array',
            'booking_details.*.course_type' => 'required|in:Private,Group,Other',
            'booking_details.*.course_id' => 'required|exists:courses,id',
            'booking_details.*.no_of_participant' => 'required|integer',
            'booking_details.*.meeting_point_id' => 'nullable|exists:meeting_points,id',
            'booking_details.*.meeting_point' => 'nullable',
            'booking_details.*.meeting_point_lat' => 'nullable',
            'booking_details.*.meeting_point_long' => 'nullable',
            'booking_details.*.lead' => 'nullable|exists:categories,id',
            'booking_details.*.contact_id' => 'nullable|exists:contacts,id',
            'booking_details.*.source_id' => 'required|exists:booking_process_sources,id',
            'booking_details.*.note' => 'nullable',
            'booking_details.*.difficulty_level' => 'required|exists:course_difficulty_levels,id',
            'booking_details.*.payee_id' => 'required|exists:contacts,id,category_id,1',
            'booking_details.*.customer_detail' => 'required|array',
            'booking_details.*.language_detail' => 'array|exists:languages,id',
            'booking_details.*.extra_participants_details' => 'array',
            'booking_details.*.is_pay' => 'boolean',
            'booking_details.*.payment_method' => 'required_if:is_pay,==,1|in:C,BT,S,P,O,CC',
            'booking_details.*.discount' => 'numeric|min:0|max:100',
        ];
    }


    /**Validation messages */
    public function messages()
    {
        return [
            /**Dynamic validation messages strings*/
            'booking_details.*.course_type.required' => 'Course type is required',
            'booking_details.*.course_type.in' => 'Course type is invalid',
            'booking_details.*.course_id.required' => 'Course id is required',
            'booking_details.*.course_id.exists' => 'Course id is invalid',
            'booking_details.*.no_of_participant.required' => 'No of participant is required',
            'booking_details.*.no_of_participant.integer' => 'No of participant must be an integer',
            'booking_details.*.meeting_point_id.sometimes' => 'Meeting point is required',
            'booking_details.*.meeting_point_id.exists' => 'Meeting point is invalid',
            'booking_details.*.lead.sometimes' => 'Lead is required',
            'booking_details.*.lead.exists' => 'Lead is invalid',
            'booking_details.*.contact_id.sometimes' => 'Contact id is required',
            'booking_details.*.contact_id.exists' => 'Contact id is invalid',
            'booking_details.*.source_id.sometimes' => 'Source id is required',
            'booking_details.*.source_id.exists' => 'Source id is invalid',
            'booking_details.*.difficulty_level.required' => 'Difficulty level is required',
            'booking_details.*.difficulty_level.exists' => 'Difficulty level is invalid',
            'booking_details.*.payee_id.required' => 'Payee is required',
            'booking_details.*.payee_id.exists' => 'Payee is invalid',
            'booking_details.*.customer_detail.required' => 'Customer detail is required',
            'booking_details.*.customer_detail.array' => 'Customer detail must be an array',
            'booking_details.*.language_detail.array' => 'Language must be an array',
            'booking_details.*.language_detail.exists' => 'Language is invalid',
            'booking_details.*.extra_participants_details.array' => 'Extra participants details must be an array',
            'booking_details.*.is_pay.boolean' => 'Is pay must be true or false',
            'booking_details.*.payment_method.required_if' => 'Payment method is required if payment is success',
            'booking_details.*.payment_method.in' => 'Payment method is invalid',
            'booking_details.*.discount.numeric' => 'Discount must be an integer',
            'booking_details.*.discount.min' => 'Discount must be an greater than 0',
            'booking_details.*.discount.max' => 'Discount must be an less than 100'
        ];
    }

    /**Return validation error response */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->sendResponse(false, $validator->errors()->first()));
    }
}
