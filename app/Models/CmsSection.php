<?php

namespace App\Models;

use App\Cms\Cms;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// One editable block of a page (text, image, button, repeatable cards)
class CmsSection extends Model
{
    use Concerns\NeverDeleted;
    use Concerns\Auditable;

    protected $fillable = ['page_id', 'section_key', 'label', 'heading', 'subheading', 'body', 'image_path', 'image_disk',
        'button_text', 'button_url', 'items', 'sort_order', 'is_active'];

    protected $casts = [
        'items' => 'array',
        'is_active' => 'boolean',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'page_id');
    }

    public function imageUrl(): ?string
    {
        return Cms::imageUrl($this->image_path, $this->image_disk);
    }

    // Visible cards only
    public function activeItems(): array
    {
        return array_values(array_filter($this->items ?? [], fn ($item) => ($item['is_active'] ?? true) && filled($item['title'] ?? null)));
    }
}
