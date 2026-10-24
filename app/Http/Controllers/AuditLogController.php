<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Audit trail viewer (FR-14). Admins see everything; owners see entries for
 * their properties and actions taken by themselves or their staff.
 */
class AuditLogController extends Controller
{
    public function index(Request $request){
        $user = $request->user();
        $properties = Property::whereIn('id', $user->accessiblePropertyIds())->orderBy('property_name')->get();

        $logs = AuditLog::with('user')
            ->when(!$user->isAdmin(), function ($q) use ($user, $properties) {
                $team = User::where('owner_id', $user->id)->pluck('id')->push($user->id);
                $q->where(fn ($w) => $w->whereIn('property_id', $properties->pluck('id'))->orWhereIn('user_id', $team));
            })
            ->when($request->filled('property_id'), fn ($q) => $q->where('property_id', $request->property_id))
            ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', $request->action.'%'))
            ->when($request->filled('from'), fn ($q) => $q->where('created_at', '>=', $request->from.' 00:00:00'))
            ->when($request->filled('to'), fn ($q) => $q->where('created_at', '<=', $request->to.' 23:59:59'))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.trim($request->q).'%';
                $q->where(fn ($w) => $w->where('description', 'like', $term)
                    ->orWhere('subject_type', 'like', $term)
                    ->orWhereIn('user_id', User::where('name', 'like', $term)->orWhere('email', 'like', $term)->select('id')));
            })
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $actions = ['created', 'updated', 'rates.', 'inventory.', 'amenity.', 'photo.', 'staff.', 'auth.'];

        return view('audit.index', compact('logs', 'properties', 'actions'));
    }
}
