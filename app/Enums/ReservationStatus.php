<?php

namespace App\Enums;

/**
 * Reservation lifecycle (standard hotel model):
 *
 *   pending ─→ confirmed ─→ checked_in ─→ checked_out
 *      │           │
 *      ├→ cancelled├→ cancelled
 *      └→ expired  └→ no_show
 *
 * pending is for online payments awaiting payment (not used while only
 * pay-at-hotel exists). cancelled / expired / no_show return the rooms to
 * inventory. checked_out, cancelled, expired and no_show are final.
 */
enum ReservationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case CheckedOut = 'checked_out';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case NoShow = 'no_show';

    /** @return self[] */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled, self::Expired],
            self::Confirmed => [self::CheckedIn, self::Cancelled, self::NoShow],
            self::CheckedIn => [self::CheckedOut],
            default => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    // Statuses that hold rooms in inventory
    public function holdsInventory(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed, self::CheckedIn, self::CheckedOut], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::CheckedIn => 'Checked in',
            self::CheckedOut => 'Checked out',
            self::Cancelled => 'Cancelled',
            self::Expired => 'Expired',
            self::NoShow => 'No show',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'badge-warning',
            self::Confirmed => 'badge-primary',
            self::CheckedIn => 'badge-info',
            self::CheckedOut => 'badge-success',
            default => 'badge-secondary',
        };
    }
}
