<?php

namespace App\Services;

use App\Models\City;
use App\Models\District;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The "Where" box of the public search: guests type a city ("Ella"), a
 * district ("Kandy District") or part of a name, without choosing a
 * province or district first.
 *
 * Matching order: exact city name, then exact district name, then partial
 * matches on city names, district names and property names.
 */
class LocationSearch
{
    /**
     * Suggestions for the box: places that have active properties.
     * [['value' => 'Ella', 'label' => 'Ella, Badulla District'], ['value' => 'Kandy District', ...]]
     */
    public function destinations(): Collection
    {
        $cities = City::with('district')
            ->whereIn('id', fn ($q) => $q->select('city_id')->from('properties')->where('is_active', true))
            ->orderBy('city_name')
            ->get()
            ->map(fn ($city) => ['value' => $city->city_name, 'label' => $city->city_name.($city->district ? ', '.$city->district->district_name.' District' : '')]);

        $districts = District::whereIn('id', fn ($q) => $q->select('cities.district_id')->from('properties')
                ->join('cities', 'cities.id', '=', 'properties.city_id')
                ->where('properties.is_active', true))
            ->orderBy('district_name')
            ->get()
            ->map(fn ($district) => ['value' => $district->district_name.' District', 'label' => 'Whole district']);

        return $cities->concat($districts)->unique('value')->values();
    }

    /**
     * What the guest typed -> how to filter properties, or null for "anywhere".
     * label: shown to the guest ("Kandy"); key: identifies the place (featured picks);
     * cityIds: properties in these cities; nameLike: also match property names.
     */
    public function resolve(?string $where): ?object
    {
        $where = trim(preg_replace('/\s+/', ' ', (string) $where));
        if ($where === '') {
            return null;
        }
        $lower = mb_strtolower($where);

        // "Kandy District" (from the suggestions)
        if (str_ends_with($lower, ' district')) {
            $district = $this->districtNamed(mb_substr($where, 0, -9));
            if ($district) {
                return $this->forDistrict($district);
            }
        }

        // Exact city name (several cities may share a name)
        $cities = City::whereRaw('LOWER(city_name) = ?', [$lower])->get();
        if ($cities->isNotEmpty()) {
            return (object) [
                'label' => $cities->first()->city_name,
                'key' => 'city:'.$cities->pluck('id')->sort()->join(','),
                'cityIds' => $cities->pluck('id')->all(),
                'nameLike' => null,
            ];
        }

        if ($district = $this->districtNamed($where)) {
            return $this->forDistrict($district);
        }

        // Partial: city or district names containing the text, or property names
        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $where).'%';
        $cityIds = City::where('city_name', 'like', $like)
            ->orWhereIn('district_id', District::where('district_name', 'like', $like)->select('id'))
            ->pluck('id')->all();

        return (object) [
            'label' => $where,
            'key' => 'text:'.$lower,
            'cityIds' => $cityIds,
            'nameLike' => $like,
        ];
    }

    // Older links: ?district_id=5
    public function forDistrictId($districtId): ?object
    {
        $district = District::find($districtId);
        return $district ? $this->forDistrict($district) : null;
    }

    // Limits a Property query to the place
    public function apply(Builder $query, object $place): Builder
    {
        return $query->where(function ($q) use ($place) {
            $q->whereIn('city_id', $place->cityIds ?: [0]);
            if ($place->nameLike) {
                $q->orWhere('property_name', 'like', $place->nameLike);
            }
        });
    }

    private function forDistrict(District $district): object
    {
        return (object) [
            'label' => $district->district_name.' District',
            'key' => 'district:'.$district->id,
            'cityIds' => City::where('district_id', $district->id)->pluck('id')->all(),
            'nameLike' => null,
        ];
    }

    private function districtNamed(string $name): ?District
    {
        return District::whereRaw('LOWER(district_name) = ?', [mb_strtolower(trim($name))])->first();
    }
}
