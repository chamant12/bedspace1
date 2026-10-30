<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Services\ImageUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

// Blog / travel journal articles (admin). Posts are unpublished, never deleted.
class BlogPostController extends Controller
{
    public function index(){
        return view('admin.cms.blog', ['posts' => BlogPost::orderByDesc('published_at')->orderByDesc('id')->get()]);
    }

    public function create(){
        return view('admin.cms.blogForm', ['post' => new BlogPost(['author_name' => 'EpickTours Editorial'])]);
    }

    public function store(Request $request, ImageUploader $uploader){
        $post = new BlogPost();
        $this->fill($post, $request, $uploader);
        return redirect('/admin/cms/blog/'.$post->id)->with('success', 'Article created.');
    }

    public function edit($post_id){
        return view('admin.cms.blogForm', ['post' => BlogPost::findOrFail($post_id)]);
    }

    public function update(Request $request, $post_id, ImageUploader $uploader){
        $post = BlogPost::findOrFail($post_id);
        $this->fill($post, $request, $uploader);
        return redirect()->back()->with('success', 'Article saved.');
    }

    public function togglePublish($post_id){
        $post = BlogPost::findOrFail($post_id);
        $post->is_published = !$post->is_published;
        if ($post->is_published && !$post->published_at) {
            $post->published_at = now();
        }
        $post->save();
        return redirect()->back()->with('success', $post->title.' is now '.($post->is_published ? 'published' : 'unpublished').'.');
    }

    private function fill(BlogPost $post, Request $request, ImageUploader $uploader): void
    {
        $request->merge(['slug' => Str::slug($request->input('slug') ?: $request->input('title'))]);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('blog_posts', 'slug')->ignore($post->id)],
            'category' => ['nullable', 'string', 'max:100'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:100000'],
            'author_name' => ['nullable', 'string', 'max:100'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $post->fill(collect($data)->except(['cover', 'is_published', 'published_at'])->all());
        $post->is_published = $request->boolean('is_published');
        $post->published_at = $data['published_at'] ?? ($post->published_at ?? ($post->is_published ? now() : null));
        if ($request->hasFile('cover')) {
            $stored = $uploader->store($request->file('cover'), 'blog');
            $post->cover_image_path = 'blog/'.$stored['file_name'];
            $post->cover_image_disk = $stored['disk'];
        }
        $post->save();
    }
}
