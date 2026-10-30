<?php

namespace App\Cms;

/**
 * Which fields each page section uses, so the admin only sees fields
 * that actually appear on the website.
 * Item fields: title, text, image, url.
 */
class SectionFields
{
    const MAP = [
        'home.hero' => ['heading', 'subheading', 'image'],
        'home.about' => ['subheading', 'heading', 'body', 'image', 'button'],
        'home.destinations' => ['subheading', 'heading', 'body', 'items' => ['title', 'text', 'image', 'url']],
        'home.featured' => ['subheading', 'heading', 'body', 'button'],
        'home.why' => ['subheading', 'heading', 'items' => ['title', 'text']],
        'home.owners' => ['subheading', 'heading', 'body', 'button'],
        'home.blog' => ['subheading', 'heading', 'button'],
        'about.intro' => ['heading', 'subheading', 'body', 'image'],
        'about.mission' => ['heading', 'body'],
        'about.travellers' => ['heading', 'items' => ['title', 'text']],
        'about.owners' => ['heading', 'body', 'button'],
        'contact.intro' => ['heading', 'subheading', 'body'],
        'blog.intro' => ['heading', 'subheading'],
    ];

    // Simple pages (terms, privacy, ...) and anything not listed
    const DEFAULT = ['heading', 'subheading', 'body'];

    public static function for(string $pageSlug, string $sectionKey): array
    {
        return self::MAP["$pageSlug.$sectionKey"] ?? self::DEFAULT;
    }

    public static function uses(array $fields, string $field): bool
    {
        return in_array($field, $fields, true) || array_key_exists($field, $fields);
    }

    public static function itemFields(array $fields): array
    {
        return $fields['items'] ?? [];
    }
}
