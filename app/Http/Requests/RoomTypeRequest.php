<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoomTypeRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'roomType' => ['required', 'string', 'max:255'],
            'max_occupancy' => ['required', 'integer', 'min:1', 'max:10'],
        ];

        // Room count is only set on create; the edit form shows it read-only
        if ($this->is('create-room-type')) {
            $rules['property_id'] = ['required', 'integer', 'exists:properties,id'];
            $rules['noOfRooms'] = ['required', 'integer', 'min:1', 'max:500'];
        } else {
            $rules['roomType_id'] = ['required', 'integer', 'exists:roomtypes,id'];
        }

        return $rules;
    }
}
