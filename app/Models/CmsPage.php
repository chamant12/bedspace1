<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// A public web page made of editable sections
class CmsPage extends Model
{
    use Concerns\NeverDeleted;
    use Concerns\Auditable;

    protected $fillable = ['slug', 'title', 'meta_description', 'is_published', 'show_in_footer', 'sort_order'];

    protected $casts = [
        'is_published' => 'boolean',
        'show_in_footer' => 'boolean',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(CmsSection::class, 'page_id')->orderBy('sort_order');
    }
}
