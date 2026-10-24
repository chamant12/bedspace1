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
