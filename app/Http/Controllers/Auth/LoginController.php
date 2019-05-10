<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
     */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        $client = new \GuzzleHttp\Client();
        $clientResponse = $client->request(
            'POST',
            env('TEAMWORK_URL') . 'launchpad/v1/login.json',
            [
                'verify' => false,
                'http_errors' => false,
                'form_params' => [
                    'email' => $request['email'],
                    'password' => $request['password'],
                    'username' => $request['email']
                ]
            ]
        );
        if ($clientResponse->getStatusCode() !== 200) {
            return $this->sendFailedLoginResponse($request, 'auth.failed_status');
        }

        $user = json_decode($clientResponse->getBody(), true);
        $userInfo = \TeamWorkPm\Factory::build('People')->get($user['userId']);
        $request->session()->put('user', json_decode($userInfo, true));
        return redirect()->intended('home');
    }

    /**
     * Validate the user login.
     * @param Request $request
     */
    public function validateLogin(Request $request)
    {
        $this->validate(
            $request,
            [
                'email' => 'required|string',
                'password' => 'required|string',
            ],
            [
                'email.required' => 'Username or email is required',
                'password.required' => 'Password is required',
            ]
        );
    }
}
