<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OwnerApprovedMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class OwnerController extends Controller
{
    public function index()
    {
        // Owners awaiting approval first
        $owners = User::where('user_type_id', User::TYPE_PROPERTY_OWNER)
            ->withCount('properties')
            ->orderByRaw('approved_at IS NULL DESC')
            ->orderBy('name')
            ->get();

        return view('admin.owners', compact('owners'));
    }

    public function create()
    {
        return view('admin.ownerCreate');
    }

    // Owners created by an admin are approved straight away
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $owner = new User();
        $owner->name = $data['name'];
        $owner->email = $data['email'];
        $owner->phone = $data['phone'];
        $owner->password = Hash::make($data['password']);
        $owner->user_role_id = 1;
        $owner->user_type_id = User::TYPE_PROPERTY_OWNER;
        $owner->approved_at = now();
        $owner->approved_by_user_id = $request->user()->id;
        $owner->save();

        return redirect('/admin/owners')->with('success', "Owner account for {$owner->name} was created and approved.");
    }

    public function approve(Request $request, $user_id)
    {
        $owner = User::where('user_type_id', User::TYPE_PROPERTY_OWNER)->whereNull('approved_at')->findOrFail($user_id);
        $owner->approved_at = now();
        $owner->approved_by_user_id = $request->user()->id;
        $owner->save();

        try {
            Mail::to($owner->email)->send(new OwnerApprovedMail($owner));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->back()->with('success', "{$owner->name} was approved and notified by email.");
    }

    // Inactive owners (and their staff) cannot sign in or work
    public function toggleStatus($user_id)
    {
        $owner = User::where('user_type_id', User::TYPE_PROPERTY_OWNER)->findOrFail($user_id);
        $owner->status = $owner->status ? 0 : 1;
        $owner->save();

        session()->flash('success', $owner->name.' was '.($owner->status ? 'activated' : 'deactivated'));
        return redirect()->back();
    }
}
