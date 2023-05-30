<?php

namespace App\Models;

use App\User;
use App\Models\Office;
use App\Models\Contact;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\ContactLeave;
use App\Models\PaymentMethod;
use App\Models\ContactAddress;
use App\Models\ContactAllergy;
use App\Models\ContactLanguage;
use App\Models\InstructorLevel;
use App\Models\InstructorBlock; 
use App\Models\ContactBankDetail;
use App\Models\ContactDifficultyLevel;
use App\Models\ContactAdditionalPerson;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BookingProcess\BookingProcessCustomerDetails;
use App\Models\BookingProcess\BookingProcessRequestInstructor;

class Contact extends Model
{
    use SoftDeletes, Notifiable;

    protected $fillable = ['salutation',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'mobile1',
        'mobile2',
        'designation',
        'nationality',
        'dob',
        'gender',
        'profile_pic',
        'color_code',
        'skiing_level',
        'instructor_number',
        'QR_number',
        'insurance_number',
        'display_in_app',
        'category_id',
        'office_id',
    //    'difficulty_level_id',
        'is_active',
        'subcategory_id',
        'other_address',
        'service_name',
        'contact_person_email',
        'contact_person_name',
        'joining_date',
        'last_booking_date',
        'addition_comments',
        'created_by',
        'updated_by',
        'total_feedback',
        'addition_comments',
        'signature',
        'accommodation_id',
        'accomodation',
        'is_ski',
        'is_snowboard'
    ];

    protected $appends = ['language_locale'];

    /* helper functions */
    public function getFullName()
    {
        $full_name = '';
        if ($this->first_name) {
            $full_name .= $this->first_name;
            $full_name .= " ";
        }
        if ($this->middle_name) {
            $full_name .= $this->middle_name;
            $full_name .= " ";
        }
        if ($this->last_name) {
            $full_name .= $this->last_name;
        }
        return $full_name;
    }

    /* relations */
    public function category_detail()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function isType($type)
    {
        return $this->category_detail->name === $type;
    }

    public function subcategory_detail()
    {
        return $this->belongsTo(Subcategory::class, 'subcategory_id');
    }

    public function allergies()
    {
        return $this->hasMany(ContactAllergy::class, 'contact_id');
    }

    public function languages()
    {
        return $this->hasMany(ContactLanguage::class, 'contact_id');
    }

    public function address()
    {
        return $this->hasMany(ContactAddress::class, 'contact_id');
    }

    public function addition_persons()
    {
        return $this->hasMany(ContactAdditionalPerson::class, 'contact_id');
    }

    public function bank_detail()
    {
        return $this->hasOne(ContactBankDetail::class, 'contact_id');
    }

    public function booking_detail()
    {
        return $this->hasMany(BookingProcessCustomerDetails::class, 'customer_id');
    }

    public function difficulty_level_detail()
    {
        return $this->hasMany(ContactDifficultyLevel::class, 'contact_id');
    }

    public function office_detail()
    {
        return $this->belongsTo(Office::class, 'office_id');
    }

    public function user_detail()
    {
        return $this->hasOne(User::class, 'contact_id');
    }

    public function payment_method()
    {
        return $this->belongsTo(PaymentMethod::class, 'prefer_payment_method_id');
    }

    public function account()
    {
        return $this->hasOne(AccountMaster::class, 'contact_id');
    }

    public function leave_data()
    {
        return $this->hasMany(ContactLeave::class, 'contact_id');
    }

    public function routeNotificationForMail()
    {
        // dd('contact', $this);
        if (env('MAILING_MODE', "test") === "test") {
            return env('TEST_EMAIL', 'parthp@zignuts.com');
        } else {
            return $this->email;
        }
    }

    public function instructorBlockDetails()
    {
        return $this->hasMany(InstructorBlock::class, 'instructor_id');
    }

    public function request_instructor_detail()
    {
        return $this->hasMany(BookingProcessRequestInstructor::class,'booking_process_id');
    }

    public function accommodation_data()
    {
        return $this->belongsTo(Contact::class,'accommodation_id');
    }

    /**Get contact language locale for send email */
    public function getLanguageLocaleAttribute()
    {
        $locale = 'en';//en: English
        $languages = $this->languages->pluck('language_id');
        if(count($languages)){
            $language_ids = Language::where('name', 'like', "%German%")
            ->whereIn('id', $languages)
            ->pluck('id');
            if(count($language_ids)){
                $locale = 'de';//de: German
            }
        }
        return $locale;
    }
}
