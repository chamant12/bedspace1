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

    if (Auth::attempt($credentials)) {
        $user = User::find(['user_type_id'=>2,'email'=>$request->email]);

        if(!$user){
            $this->logout();
        }
        $request->session()->regenerate(); // Prevent session fixation
        return redirect('/dashboard');
    }
    session()->flash('error', 'Invalid username or password');
    return redirect()->back();
    
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

    public function createPropertyOwner(){
        $user_exists = User::where('email',request()->email)->count();
        if($user_exists>0){
            session()->flash('error', 'Email address already registered with us!');
            return redirect('/signup');
        }
        $propertyOwner = new User();
        $propertyOwner->name = request()->full_name;
        $propertyOwner->email = request()->email;
        $propertyOwner->phone = request()->phone;
        $propertyOwner->password = Hash::make(request()->password);
        $propertyOwner->user_role_id = 1;
        $propertyOwner->user_type_id = 2;
        $propertyOwner->save();
        session()->flash('success', 'Signup succesfull! Please login to continue');
        return redirect('/login');
    }
}

