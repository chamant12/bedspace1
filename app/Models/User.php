<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use Concerns\NeverDeleted;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    // user_types rows seeded in create_user_types migration
    const TYPE_ADMIN = 1;
    const TYPE_PROPERTY_OWNER = 2;
    const TYPE_CUSTOMER = 3;

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
        ];
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
