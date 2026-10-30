<?php

use App\Cms\DefaultContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Website CMS: site settings, pages made of editable sections, blog
     * posts and messages from the contact form. Text fields hold Markdown.
     * The default EpickTours content is installed with the tables.
     */
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('cms_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('title', 255);
            $table->string('meta_description', 300)->nullable();
            $table->boolean('is_published')->default(true);
            $table->boolean('show_in_footer')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('cms_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('cms_pages')->restrictOnDelete();
            $table->string('section_key', 50);   // where the page template places it (hero, about, destinations, ...)
            $table->string('label', 100);        // name shown in the admin
            $table->string('heading', 255)->nullable();
            $table->string('subheading', 500)->nullable();
            $table->text('body')->nullable();    // Markdown
            $table->string('image_path', 500)->nullable();
            $table->string('image_disk', 50)->nullable(); // null = file under /public (theme image)
            $table->string('button_text', 100)->nullable();
            $table->string('button_url', 500)->nullable();
            $table->json('items')->nullable();   // repeatable cards: [{title, text, image_path, image_disk, url, is_active}]
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['page_id', 'section_key']);
        });

        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->string('excerpt', 500)->nullable();
            $table->longText('body');            // Markdown
            $table->string('category', 100)->nullable();
            $table->string('author_name', 100)->nullable();
            $table->string('cover_image_path', 500)->nullable();
            $table->string('cover_image_disk', 50)->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('email', 255);
            $table->string('phone', 50)->nullable();
            $table->string('subject', 255)->nullable();
            $table->text('message');
            $table->string('ip_address', 45)->nullable();
            $table->string('status', 20)->default('new'); // new | read | replied
            $table->timestamps();
        });

        DefaultContent::install();
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('cms_sections');
        Schema::dropIfExists('cms_pages');
        Schema::dropIfExists('site_settings');
    }
};
