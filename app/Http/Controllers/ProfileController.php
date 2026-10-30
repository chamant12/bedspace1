<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * "My profile" for dashboard users (owners, staff, admins): name, mobile,
 * email and password. Changing the email (sign-in name) or the password
 * requires the current password.
 */
class ProfileController extends Controller
{
    public function edit(Request $request){
        return view('profile', ['user' => $request->user()]);
    }

    public function update(Request $request){
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $sensitive = strcasecmp($data['email'], $user->email) !== 0 || !empty($data['password']);
        if ($sensitive && !Hash::check((string) ($data['current_password'] ?? ''), $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Enter your current password to change your email or password.']);
        }

        $user->name = $data['name'];
        $user->phone = $data['phone'];
        $user->email = $data['email'];
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return redirect('/profile')->with('success', 'Your details were saved.');
    }
}
