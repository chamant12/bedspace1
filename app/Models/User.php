<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use Concerns\NeverDeleted;
    use Concerns\Auditable;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    // user_types rows seeded in create_user_types migration
    const TYPE_ADMIN = 1;
    const TYPE_PROPERTY_OWNER = 2;
    const TYPE_CUSTOMER = 3;
    const TYPE_STAFF = 4; // hotel staff of a property owner

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'approved_at' => 'datetime',
            'can_edit_properties' => 'boolean',
        ];
    }

    public function isStaff(): bool
    {
        return (int) $this->user_type_id === self::TYPE_STAFF;
    }

    // Owner or one of their staff: the "partner" side of the platform
    public function isPartner(): bool
    {
        return $this->isOwner() || $this->isStaff();
    }

    // The property owner whose business this user works in
    public function ownerId(): ?int
    {
        return match (true) {
            $this->isOwner() => (int) $this->id,
            $this->isStaff() => $this->owner_id ? (int) $this->owner_id : null,
            default => null,
        };
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Owners must be approved by an admin before adding properties, staff,
     * rates etc.; staff follow their owner's approval.
     */
    public function isApproved(): bool
    {
        return match (true) {
            $this->isOwner() => $this->approved_at !== null,
            $this->isStaff() => (bool) $this->owner?->approved_at && (bool) $this->owner?->status,
            default => true,
        };
    }

    // "Edit property details" permission: always the owner; staff only when granted
    public function canEditProperties(): bool
    {
        return $this->isOwner() || ($this->isStaff() && $this->can_edit_properties);
    }

    // Properties this user may work with: owner = own, staff = assigned, admin = all
    public function accessiblePropertyIds(): array
    {
        return match (true) {
            $this->isAdmin() => Property::pluck('id')->all(),
            $this->isOwner() => Property::where('property_owner_id', $this->id)->pluck('id')->all(),
            $this->isStaff() => DB::table('property_staff')
                ->join('properties', 'properties.id', '=', 'property_staff.property_id')
                ->where(['property_staff.user_id' => $this->id, 'property_staff.is_active' => true, 'properties.property_owner_id' => $this->owner_id])
                ->pluck('property_staff.property_id')->all(),
            default => [],
        };
    }

    public function canAccessProperty(Property $property): bool
    {
        return in_array((int) $property->id, array_map('intval', $this->accessiblePropertyIds()), true);
    }

    // Staff: properties they are assigned to
    public function assignedProperties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'property_staff')->withPivot('is_active')->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return (int) $this->user_type_id === self::TYPE_ADMIN;
    }

    public function isOwner(): bool
    {
        return (int) $this->user_type_id === self::TYPE_PROPERTY_OWNER;
    }

    public function isCustomer(): bool
    {
        return (int) $this->user_type_id === self::TYPE_CUSTOMER;
    }

    public function dashboardUrl(): string
    {
        return match (true) {
            $this->isAdmin() => '/admin/dashboard',
            $this->isCustomer() => '/account/bookings',
            default => '/dashboard',
        };
    }

    public function userType(): BelongsTo
    {
        return $this->belongsTo(UserType::class);
    }

    public function userRole(): BelongsTo
    {
        return $this->belongsTo(UserRole::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'property_owner_id');
    }
}
