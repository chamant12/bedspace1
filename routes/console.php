<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Validator;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Releases rooms held by unpaid online bookings (runs every 5 minutes via the scheduler)
Artisan::command('bedspace:expire-pending', function (App\Services\Reservations $reservations) {
    $this->info($reservations->expirePending().' pending booking(s) expired');
})->purpose('Expire unpaid pending bookings');
Schedule::command('bedspace:expire-pending')->everyFiveMinutes();

// Admins have no public signup; create them from the server shell
Artisan::command('bedspace:create-admin', function () {
    $data = [
        'name' => $this->ask('Name'),
        'email' => $this->ask('Email'),
        'phone' => $this->ask('Mobile'),
        'password' => $this->secret('Password (min 8 characters)'),
    ];

    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email'],
        'phone' => ['required', 'string', 'max:50'],
        'password' => ['required', 'string', 'min:8'],
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }
        return 1;
    }

    $admin = new User();
    $admin->name = $data['name'];
    $admin->email = $data['email'];
    $admin->phone = $data['phone'];
    $admin->password = Hash::make($data['password']);
    $admin->user_role_id = 1;
    $admin->user_type_id = User::TYPE_ADMIN;
    $admin->save();

    $this->info("Admin {$admin->email} created. Sign in at /admin/login");
})->purpose('Create a platform admin account');
