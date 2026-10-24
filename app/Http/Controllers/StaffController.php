<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Owners create hotel staff accounts and assign them to their properties.
 * Every staff member can view property details, rates and inventory and
 * manage reservations of their assigned properties; the "edit property
 * details" permission additionally lets them change property data.
 * Only owners create properties and staff. Accounts are deactivated,
 * never deleted.
 */
class StaffController extends Controller
{
    public function index(Request $request){
        $staff = User::with(['assignedProperties' => fn ($q) => $q->wherePivot('is_active', true)])
            ->where(['user_type_id' => User::TYPE_STAFF, 'owner_id' => $request->user()->id])
            ->orderBy('name')
            ->get();

        return view('staff.index', compact('staff'));
    }

    public function create(Request $request){
        return view('staff.form', ['member' => null, 'properties' => $this->ownProperties($request), 'assigned' => []]);
    }

    public function store(Request $request){
        $data = $this->validated($request, null);

        DB::transaction(function () use ($request, $data) {
            $member = new User();
            $member->name = $data['name'];
            $member->email = $data['email'];
            $member->phone = $data['phone'];
            $member->password = Hash::make($data['password']);
            $member->user_role_id = 1;
            $member->user_type_id = User::TYPE_STAFF;
            $member->owner_id = $request->user()->id;
            $member->can_edit_properties = $request->boolean('can_edit_properties');
            $member->approved_at = now();
            $member->save();

            $this->syncProperties($member, $data['property_ids'] ?? []);
        });

        return redirect('/staff')->with('success', "Staff account for {$data['name']} was created. They sign in at /login with their email.");
    }

    public function edit(Request $request, $user_id){
        $member = $this->ownStaff($request, $user_id);
        $assigned = DB::table('property_staff')->where(['user_id' => $member->id, 'is_active' => true])->pluck('property_id')->all();

        return view('staff.form', ['member' => $member, 'properties' => $this->ownProperties($request), 'assigned' => $assigned]);
    }

    public function update(Request $request, $user_id){
        $member = $this->ownStaff($request, $user_id);
        $data = $this->validated($request, $member);

        DB::transaction(function () use ($request, $member, $data) {
            $member->name = $data['name'];
            $member->email = $data['email'];
            $member->phone = $data['phone'];
            if (!empty($data['password'])) {
                $member->password = Hash::make($data['password']);
            }
            $member->can_edit_properties = $request->boolean('can_edit_properties');
            $member->save();

            $this->syncProperties($member, $data['property_ids'] ?? []);
        });

        return redirect('/staff')->with('success', "{$member->name} was updated.");
    }

    // Inactive staff cannot sign in
    public function toggleStatus(Request $request, $user_id){
        $member = $this->ownStaff($request, $user_id);
        $member->status = $member->status ? 0 : 1;
        $member->save();

        return redirect()->back()->with('success', $member->name.' was '.($member->status ? 'activated' : 'deactivated'));
    }

    private function validated(Request $request, ?User $member): array
    {
        $ownPropertyIds = $this->ownProperties($request)->pluck('id')->all();

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($member?->id)],
            'phone' => ['required', 'string', 'max:50'],
            'password' => [$member ? 'nullable' : 'required', 'string', 'min:8'],
            'property_ids' => ['nullable', 'array'],
            'property_ids.*' => ['integer', Rule::in($ownPropertyIds)],
            'can_edit_properties' => ['nullable', 'boolean'],
        ]);
    }

    // Assignments are switched on / off, never deleted
    private function syncProperties(User $member, array $propertyIds): void
    {
        $propertyIds = array_map('intval', $propertyIds);

        DB::table('property_staff')->where('user_id', $member->id)->whereNotIn('property_id', $propertyIds)
            ->update(['is_active' => false, 'updated_at' => now()]);

        foreach ($propertyIds as $propertyId) {
            DB::table('property_staff')->updateOrInsert(
                ['property_id' => $propertyId, 'user_id' => $member->id],
                ['is_active' => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    private function ownStaff(Request $request, $user_id): User
    {
        return User::where(['user_type_id' => User::TYPE_STAFF, 'owner_id' => $request->user()->id])->findOrFail($user_id);
    }

    private function ownProperties(Request $request)
    {
        return Property::where('property_owner_id', $request->user()->id)->orderBy('property_name')->get();
    }
}
