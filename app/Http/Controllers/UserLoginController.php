<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

use Illuminate\Http\Request;

class UserLoginController extends Controller
{
    public function login()
    {
        return view('login');
    }

    public function authenticateUser(Request $request){
        $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    // Only active property owners may sign in to this panel
    $credentials['status'] = 1;
    $credentials['user_type_id'] = User::TYPE_PROPERTY_OWNER;

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate(); // Prevent session fixation
        return redirect('/dashboard');
    }
    session()->flash('error', 'Invalid username or password');
    return redirect()->back()->withInput($request->only('email'));

    }

    public function logout(Request $request){
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function propertyOwnerSignup(){
        return view('signup');
    }

    public function createPropertyOwner(Request $request){
        $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'email.unique' => 'Email address already registered with us!',
        ]);

        $propertyOwner = new User();
        $propertyOwner->name = $request->full_name;
        $propertyOwner->email = $request->email;
        $propertyOwner->phone = $request->phone;
        $propertyOwner->password = Hash::make($request->password);
        $propertyOwner->user_role_id = 1;
        $propertyOwner->user_type_id = User::TYPE_PROPERTY_OWNER;
        $propertyOwner->save();
        session()->flash('success', 'Signup succesfull! Please login to continue');
        return redirect('/login');
    }
}
