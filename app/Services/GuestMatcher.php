<?php

namespace App\Services;

use App\Models\GuestProfile;
use App\Models\User;

/**
 * Finds the guest profile a booking belongs to, so a returning guest is
 * recognised even without an account. Matching order:
 *   1. the guest's account (if logged in)
 *   2. same email address
 *   3. same phone number (last 9 digits, so 077..., +9477... and 9477... match)
 *   4. same name + address
 * Otherwise a new profile is created. Missing details are filled in from
 * the latest booking; existing details are not overwritten.
 */
class GuestMatcher
{
    /**
     * @param array{name: string, email: string, phone: string, address?: ?string, country?: ?string} $guest
     */
    public function match(array $guest, ?User $account = null): GuestProfile
    {
        $keys = [
            'email_key' => $this->emailKey($guest['email'] ?? null),
            'phone_key' => $this->phoneKey($guest['phone'] ?? null),
            'name_address_key' => $this->nameAddressKey($guest['name'] ?? null, $guest['address'] ?? null),
        ];

        $profile = ($account ? GuestProfile::where('user_id', $account->id)->first() : null)
            ?? ($keys['email_key'] ? GuestProfile::where('email_key', $keys['email_key'])->orderBy('id')->first() : null)
            ?? ($keys['phone_key'] ? GuestProfile::where('phone_key', $keys['phone_key'])->orderBy('id')->first() : null)
            ?? ($keys['name_address_key'] ? GuestProfile::where('name_address_key', $keys['name_address_key'])->orderBy('id')->first() : null)
            ?? new GuestProfile();

        foreach (['name', 'email', 'phone', 'address', 'country'] as $field) {
            if (blank($profile->$field) && filled($guest[$field] ?? null)) {
                $profile->$field = $guest[$field];
            }
        }
        foreach ($keys as $key => $value) {
            if (blank($profile->$key) && $value) {
                $profile->$key = $value;
            }
        }
        if ($account && !$profile->user_id) {
            $profile->user_id = $account->id;
        }

        $profile->save();

        return $profile;
    }

    private function emailKey(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));
        return $email !== '' ? $email : null;
    }

    private function phoneKey(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        return strlen($digits) >= 7 ? substr($digits, -9) : null;
    }

    private function nameAddressKey(?string $name, ?string $address): ?string
    {
        $normalise = fn ($value) => preg_replace('/[^a-z0-9]+/', ' ', strtolower(trim((string) $value)));
        $name = trim($normalise($name));
        $address = trim($normalise($address));

        return ($name !== '' && $address !== '') ? hash('sha256', $name.'|'.$address) : null;
    }
}
