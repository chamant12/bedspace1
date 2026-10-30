<?php

namespace App\Cms;

use App\Models\CmsPage;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Reads website content for the public pages: site settings, pages and
 * their sections, Markdown rendering and image URLs.
 */
class Cms
{
    const SETTINGS_CACHE_KEY = 'cms.settings';

    // All settings as [key => value]; cached until the admin saves settings
    public static function settings(): array
    {
        return Cache::rememberForever(self::SETTINGS_CACHE_KEY, fn () => SiteSetting::pluck('value', 'key')->all());
    }

    public static function setting(string $key, ?string $default = null): ?string
    {
        $value = self::settings()[$key] ?? null;
        return filled($value) ? $value : $default;
    }

    public static function forgetSettings(): void
    {
        Cache::forget(self::SETTINGS_CACHE_KEY);
    }

    // Published page with its active sections keyed by section_key, or null
    public static function page(string $slug): ?CmsPage
    {
        $page = CmsPage::with(['sections' => fn ($q) => $q->where('is_active', true)])
            ->where(['slug' => $slug, 'is_published' => true])
            ->first();

        $page?->setRelation('sections', $page->sections->keyBy('section_key'));

        return $page;
    }

    /**
     * Markdown to HTML. Raw HTML in the text is escaped and unsafe links
     * (javascript: etc.) are dropped, so editors cannot inject scripts.
     */
    public static function markdown(?string $text): HtmlString
    {
        return new HtmlString(Str::markdown((string) $text, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]));
    }

    // Uploaded images live on a storage disk; default images are files under /public
    public static function imageUrl(?string $path, ?string $disk): ?string
    {
        if (blank($path)) {
            return null;
        }
        return $disk ? Storage::disk($disk)->url($path) : asset($path);
    }

    // Footer / menu pages (terms, privacy, ...)
    public static function footerPages()
    {
        return Cache::remember('cms.footer_pages', 600, fn () => CmsPage::where(['is_published' => true, 'show_in_footer' => true])
            ->orderBy('sort_order')->get(['slug', 'title']));
    }

    // Site link for a page slug (some pages have their own routes)
    public static function pageUrl(string $slug): string
    {
        return match ($slug) {
            'home' => url('/'),
            'about' => url('/about'),
            'contact' => url('/contact'),
            'blog' => url('/blog'),
            default => url('/page/'.$slug),
        };
    }
}
