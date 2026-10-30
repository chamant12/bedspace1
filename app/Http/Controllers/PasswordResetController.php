<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * "Forgot password" for every account type (owners, staff, admins, guests),
 * using Laravel's password broker: a single-use link emailed to the user,
 * valid for 60 minutes.
 */
class PasswordResetController extends Controller
{
    public function requestForm(){
        return view('auth.forgotPassword');
    }

    // Same answer whether or not the email exists, so accounts cannot be discovered
    public function sendLink(Request $request){
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->email)->where('status', 1)->first();
        if ($user) {
            Password::sendResetLink(['email' => $user->email]);
        }

        return back()->with('success', 'If an account exists for that email, we have sent a link to reset the password. Please check your inbox.');
    }

    public function resetForm(Request $request, string $token){
        return view('auth.resetPassword', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request){
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $resetUser = null;
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use (&$resetUser) {
                $user->password = Hash::make($password);
                $user->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
                $resetUser = $user;
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))
                ->with('error', 'This reset link is invalid or has expired. Please request a new one.');
        }

        $login = match (true) {
            $resetUser?->isAdmin() => '/admin/login',
            $resetUser?->isCustomer() => '/account/login',
            default => '/login',
        };

        return redirect($login)->with('success', 'Your password has been changed. You can sign in now.');
    }
}
