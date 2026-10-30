<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OwnerApprovedMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use App\Services\PropertyStatus;

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

    public function edit($user_id)
    {
        $owner = User::where('user_type_id', User::TYPE_PROPERTY_OWNER)->withCount('properties')->findOrFail($user_id);
        $properties = $owner->properties()->orderBy('property_name')->get();

        return view('admin.ownerEdit', compact('owner', 'properties'));
    }

    // Name, email (sign-in name), mobile and, optionally, a new password
    public function update(Request $request, $user_id)
    {
        $owner = User::where('user_type_id', User::TYPE_PROPERTY_OWNER)->findOrFail($user_id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($owner->id)],
            'phone' => ['required', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $owner->name = $data['name'];
        $owner->email = $data['email'];
        $owner->phone = $data['phone'];
        if (!empty($data['password'])) {
            $owner->password = Hash::make($data['password']);
        }
        $owner->save();

        return redirect('/admin/owners/'.$owner->id)->with('success', "{$owner->name}'s details were saved.");
    }

    /**
     * Disabling an owner stops them and their staff signing in and disables
     * all their properties at once; enabling brings those properties back.
     */
    public function toggleStatus($user_id, PropertyStatus $status)
    {
        $owner = User::where('user_type_id', User::TYPE_PROPERTY_OWNER)->findOrFail($user_id);

        if ($owner->status) {
            $count = $status->disableOwner($owner);
            $message = "{$owner->name} was disabled, together with $count propert".($count === 1 ? 'y' : 'ies').'.';
        } else {
            $count = $status->enableOwner($owner);
            $message = "{$owner->name} was enabled, and $count propert".($count === 1 ? 'y was' : 'ies were').' enabled again.';
        }

        return redirect()->back()->with('success', $message);
    }
}
