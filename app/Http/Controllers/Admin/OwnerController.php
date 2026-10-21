<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class OwnerController extends Controller
{
    public function index()
    {
        $owners = User::where('user_type_id', User::TYPE_PROPERTY_OWNER)
            ->withCount('properties')
            ->orderBy('name')
            ->get();

        return view('admin.owners', compact('owners'));
    }

    // Inactive owners cannot sign in (see UserLoginController::attemptLogin)
    public function toggleStatus($user_id)
    {
        $owner = User::where('user_type_id', User::TYPE_PROPERTY_OWNER)->findOrFail($user_id);
        $owner->status = $owner->status ? 0 : 1;
        $owner->save();

        session()->flash('success', $owner->name.' was '.($owner->status ? 'activated' : 'deactivated'));
        return redirect()->back();
    }
}
