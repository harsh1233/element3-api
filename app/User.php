<?php

namespace App;

use App\Models\Contact;
use App\Models\Language;
use App\Models\AccountMaster;
use App\Models\Permissions\Role;
use Laravel\Passport\HasApiTokens;
use App\Models\Finance\Expenditure;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\InstructorActivity\InstructorActivityTimesheet;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password','role','is_active','contact_id','is_app_user','email_token','device_token','is_verified','is_third_party_user','openfire_registration','jabber_id','register_code','register_code_expire_at','register_code_verified_at','is_duo_loggedin'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $appends = ['language_locale'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function contact_detail()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function role_detail()
    {
        return $this->belongsTo(Role::class, 'role');
    }

    public function timesheets()
    {
        return $this->hasMany(InstructorActivityTimesheet::class, 'instructor_id');
    }

    public function expenditures()
    {
        return $this->hasMany(Expenditure::class);
    }

    public function accessTokens()
    {
        return $this->hasMany('App\OauthAccessToken');
    }

    /* Helper Functions */
    public function type()
    {
        if ($this->is_app_user) {
            // is app user
            return ($this->contact_detail && $this->contact_detail->category_detail) ? $this->contact_detail->category_detail->name : null;
        } else {
            // is web user
            return $this->role_detail->code ?? null;
        }
    }

    public function getAccount()
    {
        if (!$this->contact_detail) {
            dd('ERROR : UserModel@getAccount function - no account can be created if not a contact.');
        }

        if ($this->contact_detail->account) {
            return $this->contact_detail->account;
        }

        return AccountMaster::create([
            'contact_id' => $this->contact_detail->id,
        ]);
    }

    public function routeNotificationForMail()
    {
        if (env('MAILING_MODE', "test") === "test") {
            return env('TEST_EMAIL', 'parthp@zignuts.com');
        } else {
            return $this->email;
        }
    }

    /**Get user language locale for send email */
    public function getLanguageLocaleAttribute()
    {
        $locale = 'en';//en: English
        if($this->contact_detail){
            if($this->contact_detail->languages){
                $languages = $this->contact_detail->languages->pluck('language_id');
                if(count($languages)){
                    $language_ids = Language::where('name', 'like', "%German%")
                    ->whereIn('id', $languages)
                    ->pluck('id');
                    if(count($language_ids)){
                        $locale = 'de';//de: German
                    }
                }
            }
        }
        return $locale;
    }
}
