<?php

namespace App\Http\Requests;

use App\Models\CurrencyType;
use Illuminate\Foundation\Http\FormRequest;

class PropertyRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'property_name' => ['required', 'string', 'max:255'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'address' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            // Primary contact person (stored in property_contacts)
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ];

        // A new property must come with an exchange rate for every foreign
        // currency, so foreign-currency prices always have a rate to use
        if ($this->is('create-property')) {
            foreach (CurrencyType::active()->where('is_base', false)->get() as $currency) {
                $rules["exchange_rates.{$currency->id}"] = ['required', 'numeric', 'gt:0', 'max:1000000'];
            }
        }

        return $rules;
    }

    public function attributes(): array
    {
        return CurrencyType::active()->where('is_base', false)->get()
            ->mapWithKeys(fn ($currency) => ["exchange_rates.{$currency->id}" => "{$currency->code} exchange rate"])
            ->all();
    }

    // [currency_type_id => LKR per 1 unit]
    public function exchangeRates(): array
    {
        return $this->validated('exchange_rates', []);
    }

    public function propertyData(): array
    {
        return $this->safe()->only(['property_name', 'city_id', 'address', 'location']);
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
