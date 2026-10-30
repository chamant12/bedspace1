<?php

namespace App\Http\Controllers;

use App\Cms\Cms;
use App\Mail\ContactMessageMail;
use App\Models\BlogPost;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * CMS-driven public pages: about, contact (with the contact form), the
 * blog, and simple pages such as terms, privacy, cookies and FAQ.
 */
class SiteController extends Controller
{
    public function about(){
        $page = Cms::page('about');
        abort_unless($page, 404);
        return view('site.about', compact('page'));
    }

    public function contact(){
        $page = Cms::page('contact');
        abort_unless($page, 404);
        return view('site.contact', compact('page'));
    }

    // Contact form: saved, then emailed to the admin with the visitor as reply-to
    public function sendContact(Request $request){
        // Hidden "website" field: real visitors leave it empty, spam bots fill it in
        if ($request->filled('website')) {
            return redirect('/contact')->with('success', 'Thank you for your message. We will get back to you soon.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $message = ContactMessage::create($data + ['ip_address' => $request->ip(), 'status' => 'new']);

        $recipient = Cms::setting('contact_email', config('mail.admin_address'));
        if ($recipient) {
            try {
                Mail::to($recipient)->send(new ContactMessageMail($message));
            } catch (\Throwable $e) {
                report($e); // the message is saved and visible in the dashboard either way
            }
        }

        return redirect('/contact')->with('success', 'Thank you for your message. We will get back to you soon.');
    }

    // Terms, privacy, cookies, FAQ and any other page created in the CMS
    public function page(string $slug){
        // Pages with their own routes
        if (in_array($slug, ['home', 'about', 'contact', 'blog'], true)) {
            return redirect(Cms::pageUrl($slug), 301);
        }
        $page = Cms::page($slug);
        abort_unless($page, 404);
        return view('site.page', compact('page'));
    }

    public function blog(Request $request){
        $page = Cms::page('blog');
        $category = $request->query('category');
        $posts = BlogPost::published()
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderByDesc('published_at')
            ->paginate(9)
            ->withQueryString();
        $categories = BlogPost::published()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category');

        return view('blog.index', compact('page', 'posts', 'categories', 'category'));
    }

    public function post(string $slug){
        $post = BlogPost::published()->where('slug', $slug)->firstOrFail();
        $related = BlogPost::published()->where('id', '!=', $post->id)
            ->orderByRaw('category = ? DESC', [$post->category])
            ->orderByDesc('published_at')->take(3)->get();

        return view('blog.show', compact('post', 'related'));
    }
}
