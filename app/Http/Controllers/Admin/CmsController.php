<?php

namespace App\Http\Controllers\Admin;

use App\Cms\Cms;
use App\Cms\SectionFields;
use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\CmsPage;
use App\Models\CmsSection;
use App\Models\ContactMessage;
use App\Models\SiteSetting;
use App\Services\ImageUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Website content management (admin): site settings, pages and their
 * sections. Nothing is deleted: pages are unpublished, sections and cards
 * are switched off.
 */
class CmsController extends Controller
{
    // Website settings editable in the dashboard: key => [label, type]
    const SETTINGS = [
        'site_name' => ['Website name', 'text'],
        'tagline' => ['Tagline', 'text'],
        'meta_description' => ['Default description for search engines', 'textarea'],
        'contact_email' => ['Contact email (also receives contact-form messages)', 'email'],
        'contact_phone' => ['Phone', 'text'],
        'whatsapp' => ['WhatsApp number', 'text'],
        'address' => ['Address', 'textarea'],
        'office_hours' => ['Office hours', 'text'],
        'facebook_url' => ['Facebook page URL', 'url'],
        'instagram_url' => ['Instagram URL', 'url'],
        'tripadvisor_url' => ['Tripadvisor URL', 'url'],
        'youtube_url' => ['YouTube URL', 'url'],
        'footer_about' => ['Footer text', 'textarea'],
        'copyright' => ['Copyright line (after the year)', 'text'],
    ];

    public function index(){
        return view('admin.cms.index', [
            'pages' => CmsPage::withCount('sections')->orderBy('sort_order')->get(),
            'postCount' => BlogPost::count(),
            'publishedCount' => BlogPost::published()->count(),
            'newMessages' => ContactMessage::where('status', 'new')->count(),
        ]);
    }

    public function settings(){
        return view('admin.cms.settings', ['settings' => Cms::settings(), 'fields' => self::SETTINGS]);
    }

    public function saveSettings(Request $request, ImageUploader $uploader){
        $rules = [];
        foreach (self::SETTINGS as $key => [$label, $type]) {
            $rules[$key] = match ($type) {
                'email' => ['nullable', 'email', 'max:255'],
                'url' => ['nullable', 'url:http,https', 'max:500'],
                'textarea' => ['nullable', 'string', 'max:2000'],
                default => ['nullable', 'string', 'max:255'],
            };
        }
        $rules['site_name'] = ['required', 'string', 'max:100'];
        $rules['logo'] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
        $data = $request->validate($rules);

        DB::transaction(function () use ($data, $request, $uploader) {
            foreach (array_keys(self::SETTINGS) as $key) {
                SiteSetting::updateOrCreate(['key' => $key], ['value' => $data[$key] ?? null]);
            }
            if ($request->hasFile('logo')) {
                $stored = $uploader->store($request->file('logo'), 'cms');
                SiteSetting::updateOrCreate(['key' => 'logo_path'], ['value' => 'cms/'.$stored['file_name']]);
                SiteSetting::updateOrCreate(['key' => 'logo_disk'], ['value' => $stored['disk']]);
            }
            if ($request->boolean('remove_logo')) {
                SiteSetting::updateOrCreate(['key' => 'logo_path'], ['value' => null]);
            }
        });
        Cms::forgetSettings();

        return redirect()->back()->with('success', 'Website settings saved.');
    }

    public function editPage($page_id){
        $page = CmsPage::with('sections')->findOrFail($page_id);
        $fields = $page->sections->mapWithKeys(fn ($section) => [$section->id => SectionFields::for($page->slug, $section->section_key)]);

        return view('admin.cms.page', compact('page', 'fields'));
    }

    public function savePage(Request $request, $page_id){
        $page = CmsPage::findOrFail($page_id);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'show_in_footer' => ['nullable', 'boolean'],
        ]);

        $page->title = $data['title'];
        $page->meta_description = $data['meta_description'] ?? null;
        $page->show_in_footer = $request->boolean('show_in_footer');
        $page->save();
        Cache::forget('cms.footer_pages');

        return redirect()->back()->with('success', 'Page saved.');
    }

    // The home page cannot be unpublished; other pages can
    public function togglePage($page_id){
        $page = CmsPage::findOrFail($page_id);
        abort_if($page->slug === 'home', 422, 'The home page is always published.');
        $page->is_published = !$page->is_published;
        $page->save();
        Cache::forget('cms.footer_pages');

        return redirect()->back()->with('success', $page->title.' is now '.($page->is_published ? 'published' : 'unpublished').'.');
    }

    // New simple page (e.g. a destination guide) with one text section
    public function createPage(Request $request){
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/'],
        ]);
        $slug = $data['slug'] ?: Str::slug($data['title']);
        $request->merge(['slug' => $slug])->validate(['slug' => [Rule::unique('cms_pages', 'slug'), Rule::notIn(['home', 'about', 'contact', 'blog'])]]);

        $page = DB::transaction(function () use ($data, $slug) {
            $page = CmsPage::create(['slug' => $slug, 'title' => $data['title'], 'is_published' => false,
                'sort_order' => (int) CmsPage::max('sort_order') + 1]);
            CmsSection::create(['page_id' => $page->id, 'section_key' => 'body', 'label' => 'Content', 'heading' => $data['title'], 'sort_order' => 0]);
            return $page;
        });

        return redirect('/admin/cms/pages/'.$page->id)->with('success', 'Page created. Add its content, then publish it.');
    }

    public function saveSection(Request $request, $section_id, ImageUploader $uploader){
        $section = CmsSection::with('page')->findOrFail($section_id);
        $fields = SectionFields::for($section->page->slug, $section->section_key);
        $link = ['nullable', 'string', 'max:500', 'regex:#^(/|https?://|\#|mailto:|tel:)#i'];

        $data = $request->validate([
            'heading' => ['nullable', 'string', 'max:255'],
            'subheading' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:60000'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_url' => $link,
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'is_active' => ['nullable', 'boolean'],
            'items' => ['nullable', 'array', 'max:40'],
            'items.*.title' => ['nullable', 'string', 'max:255'],
            'items.*.text' => ['nullable', 'string', 'max:1000'],
            'items.*.url' => $link,
            'items.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'button_url.regex' => 'Links must start with /, http:// or https://.',
            'items.*.url.regex' => 'Links must start with /, http:// or https://.',
        ]);

        foreach (['heading', 'subheading', 'body'] as $field) {
            if (SectionFields::uses($fields, $field)) {
                $section->$field = $data[$field] ?? null;
            }
        }
        if (SectionFields::uses($fields, 'button')) {
            $section->button_text = $data['button_text'] ?? null;
            $section->button_url = $data['button_url'] ?? null;
        }
        if (SectionFields::uses($fields, 'image') && $request->hasFile('image')) {
            $stored = $uploader->store($request->file('image'), 'cms');
            $section->image_path = 'cms/'.$stored['file_name'];
            $section->image_disk = $stored['disk'];
        }
        if (SectionFields::uses($fields, 'items')) {
            $section->items = $this->items($request, $section, $uploader);
        }
        $section->is_active = $request->boolean('is_active');
        $section->save();

        return redirect('/admin/cms/pages/'.$section->page_id.'#section-'.$section->id)->with('success', ($section->label ?: 'Section').' saved.');
    }

    // Repeatable cards: rows without a title are dropped; images kept unless replaced
    private function items(Request $request, CmsSection $section, ImageUploader $uploader): array
    {
        $existing = $section->items ?? [];
        $items = [];
        foreach ((array) $request->input('items', []) as $index => $row) {
            if (blank($row['title'] ?? null)) {
                continue;
            }
            $item = [
                'title' => $row['title'],
                'text' => $row['text'] ?? null,
                'url' => $row['url'] ?? null,
                'image_path' => $existing[$index]['image_path'] ?? null,
                'image_disk' => $existing[$index]['image_disk'] ?? null,
                'is_active' => !empty($row['is_active']),
            ];
            if ($request->hasFile("items.$index.image")) {
                $stored = $uploader->store($request->file("items.$index.image"), 'cms');
                $item['image_path'] = 'cms/'.$stored['file_name'];
                $item['image_disk'] = $stored['disk'];
            }
            $items[] = $item;
        }
        return $items;
    }
}
