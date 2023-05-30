<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use LaravelDuo\LaravelDuo;

class HomeController extends Controller
{
    private $_laravelDuo;

    function __construct(LaravelDuo $laravelDuo)
    {
        $this->_laravelDuo = $laravelDuo;
    }

    /**
     * Stage Two - The Duo Auth form
     */
    public function authduologin()
    {
        /**
         * Validate the user details, but don't log the user in
         */
            $U = $_GET['email'];

            $duoinfo = array(
                'HOST' => $this->_laravelDuo->get_host(),
                'POST' => url('duologin'),
                'USER' => $U,
                'SIG'  => $this->_laravelDuo->signRequest($this->_laravelDuo->get_ikey(), $this->_laravelDuo->get_skey(), $this->_laravelDuo->get_akey(), $U)
            );

            return view('duologin')->with(compact('duoinfo'));
    }

    /**
     * Stage Three - After Duo Auth Form
     */
    public function postDuologin()
    {
        /**
         * Sent back from Duo
         */
        $response = $_POST['sig_response'];

        $U = $this->_laravelDuo->verifyResponse($this->_laravelDuo->get_ikey(), $this->_laravelDuo->get_skey(), $this->_laravelDuo->get_akey(), $response);

        /**
         * Duo response returns USER field from Stage Two
         */
        if($U){

            /**
             * Get the id of the authenticated user from their email address
             */
            $user = User::where('email', $U)->first();
            
            return Redirect::to('https://e3-qkmountain.qkinnovations.com/element3-crm/booking-process/calender-category');

            /**
             * Log the user in by their ID
             */
            //Auth::loginUsingId($user->id);

            /**
             * Check Auth worked, redirect to homepage if so
             */
            // if(Auth::check())
            // {
            //     return $this->sendResponse(true, 'success');
            // }
        }
    }
}
