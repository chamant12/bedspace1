<?php

namespace App\Models;

use App\Cms\Cms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

// Travel blog article (Markdown body); unpublished instead of deleted
class BlogPost extends Model
{
    use Concerns\NeverDeleted;
    use Concerns\Auditable;

    protected $fillable = ['title', 'slug', 'excerpt', 'body', 'category', 'author_name', 'cover_image_path', 'cover_image_disk',
        'meta_description', 'is_published', 'published_at'];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->where('published_at', '<=', now());
    }

    public function coverUrl(): ?string
    {
        return Cms::imageUrl($this->cover_image_path, $this->cover_image_disk);
    }

    public function readingMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags($this->body)) / 200));
    }
}
