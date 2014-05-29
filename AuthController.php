<?php


class AuthController extends BaseController {

    public function getLogin()
    {
        return View::make('auth.login');
    }

    public function postLogin()
    {
        $credentials = array(
            'email'    => Input::get('email'),
            'password' => Input::get('password')
        );

        try
        {
            Session::forget("active_site");
            $remember = (Input::get("remember-me")=="on")?true:false;
            $user = Sentry::authenticate($credentials, $remember);

            if ($user)
            {
                //check if they have a site
                $site = Site::where("user_id", $user->id)->first();
                if ($site)
                {
                    Session::set("active_site", $site->id);
                }else if ($user->hasAccess("admin"))
                {
                    //Session::set("active_site", Site::first()->id);
                    return Redirect::route('admin.user.index');
                }else{
                    return Redirect::route('campaign.new')->with("notice", "To get started, create a campaign with the hashtags you want to listen for.");
                }
                return Redirect::route('dashboard.index');
            }
        }
        catch(\Exception $e)
        {
            return Redirect::route('login')->withErrors(array('login' => "Error logging in, please check your username and password"));
        }
    }

    public function getLogout()
    {
        Sentry::logout();
        Session::flush();

        return Redirect::route('login');
    }

    public function getForgot()
    {
        return View::make('auth.forgot');
    }

    public function postForgot()
    {
        $email = Input::get("email");
        try
        {
            $user = Sentry::getUserProvider()->findByLogin($email);
            if ($user)
            {
                $resetCode = $user->getResetPasswordCode();

                //email now

                $resetURL = URL::route("auth.get.forgot.reset", array("reset" => $resetCode));
                //var_dump($resetURL);die;
                Mail::send('emails.reminder', array("reset" => $resetURL), function ($message) use ($email) {
                    $message->subject('Dittto.com - Password Reminder');
                    $message->from('noreply@dittto.com', 'Dittto.com');
                    $message->to($email); // Recipient address
                });
            }
        }
        catch(\Exception $e)
        {

        }
        return Redirect::route('auth.get.forgot')->withErrors(array('login' => "If the email exists, a reset code will be sent."));
    }

    public function getForgotReset($reset)
    {
        try
        {
            $user = Sentry::getUserProvider()->findByResetPasswordCode($reset);
            if ($user)
            {
                return View::make('auth.reset');
            }
        }catch(\Exception $e)
        {

        }
        return Redirect::route('login');
    }

    public function postForgotReset($reset)
    {
        $password1 = Input::get("password1");
        $password2 = Input::get("password2");



        try
        {
            $user = Sentry::getUserProvider()->findByResetPasswordCode($reset);
            if ($user)
            {
                if ($password1 != $password2)
                {
                    return Redirect::route('auth.get.forgot.reset', array("reset" => $reset))->withErrors(array('login' => "Your passwords did not match"));
                }

                if (strlen($password1) < 6)
                {
                    return Redirect::route('auth.get.forgot.reset', array("reset" => $reset))->withErrors(array('login' => "Your password must be longer than 6 characters"));
                }

                if ($user->attemptResetPassword($reset, $password1))
                {
                    // Password reset passed
                    return Redirect::route('login', array("reset" => $reset))->withErrors(array('login' => "Your password has been updated!"));
                }
                else
                {
                    // Password reset failed
                    return Redirect::route('auth.get.forgot.reset', array("reset" => $reset))->withErrors(array('login' => "Failed to update password, please try again."));
                }
            }
        }catch(\Exception $e)
        {

        }
        return Redirect::route('login');
    }

}