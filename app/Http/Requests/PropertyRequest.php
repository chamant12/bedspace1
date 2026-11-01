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
            // Pin on the map (both or neither), inside Sri Lanka
            'latitude' => ['nullable', 'numeric', 'between:5.5,10.2', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:79.3,82.2', 'required_with:latitude'],
            // Cancellation policy: no refund within this many days of check-in
            'cancellation_days' => ['required', 'integer', 'min:0', 'max:365'],
            // Primary contact person (stored in property_contacts)
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.between' => 'The map pin must be in Sri Lanka.',
            'longitude.between' => 'The map pin must be in Sri Lanka.',
        ];
    }

    public function propertyData(): array
    {
        $data = $this->safe()->only(['property_name', 'city_id', 'address', 'latitude', 'longitude', 'cancellation_days']);
        // No pin: store nulls (the pin was removed)
        $data['latitude'] = $data['latitude'] ?? null;
        $data['longitude'] = $data['longitude'] ?? null;
        return $data;
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
