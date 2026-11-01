<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    use Concerns\NeverDeleted;
    use Concerns\Auditable;

    protected $casts = [
        'has_thumbnail' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Tags owners can choose for a gallery type: [key => label]
    public static function tagsFor(string $uploadType): array
    {
        return config("bedspace.photo_tags.$uploadType", []);
    }

    // "Swimming pool", or null when not tagged
    public function getTagLabelAttribute(): ?string
    {
        return $this->tag ? (self::tagsFor($this->upload_type)[$this->tag] ?? null) : null;
    }

    public function getUrlAttribute(): string
    {
        return $this->storageUrl($this->file_name);
    }

    public function getThumbUrlAttribute(): string
    {
        // Photos uploaded before thumbnails existed fall back to the full image
        return $this->has_thumbnail ? $this->storageUrl('thumb_'.$this->file_name) : $this->url;
    }

    private function storageUrl(string $fileName): string
    {
        return Storage::disk($this->disk)->url(trim($this->upload_path, '/').'/'.$fileName);
    }
}
