<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PropertyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'property_name' => ['required', 'string', 'max:255'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'address' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            // Cancellation policy: no refund within this many days of check-in
            'cancellation_days' => ['required', 'integer', 'min:0', 'max:365'],
            // Primary contact person (stored in property_contacts)
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function propertyData(): array
    {
        return $this->safe()->only(['property_name', 'city_id', 'address', 'location', 'cancellation_days']);
    }

    public function contactData(): array
    {
        return [
            'name' => $this->validated('contact_name'),
            'mobile' => $this->validated('contact_number'),
            'email' => $this->validated('contact_email'),
        ];
    }
}
