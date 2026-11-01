<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    use Concerns\NeverDeleted;
    use Concerns\Auditable;

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Groups for a type, in display order: [key => label]
    public static function categoriesFor(string $amenityType): array
    {
        return config("bedspace.amenity_categories.$amenityType", []);
    }

    /**
     * Amenities grouped for display: [category label => Collection of amenities],
     * groups in config order, amenities by sort order then name.
     */
    public static function grouped(string $amenityType, $amenities): \Illuminate\Support\Collection
    {
        $categories = self::categoriesFor($amenityType);
        $order = array_flip(array_keys($categories));

        return collect($amenities)
            ->sortBy(fn ($amenity) => sprintf('%03d|%05d|%s', $order[$amenity->category] ?? 999, $amenity->sort_order, mb_strtolower($amenity->amenity)))
            ->groupBy(fn ($amenity) => $categories[$amenity->category] ?? 'Other');
    }
}
