<?php

namespace App\Cms;

use App\Models\BlogPost;
use App\Models\CmsPage;
use App\Models\CmsSection;
use App\Models\SiteSetting;

/**
 * The starting content of the EpickTours website, installed with the CMS
 * tables. Everything here is editable afterwards in Dashboard > Website
 * content. Images point to the theme's placeholder photos until real
 * photographs are uploaded.
 */
class DefaultContent
{
    public static function install(): void
    {
        foreach (self::settings() as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        foreach (self::pages() as $order => $page) {
            $model = CmsPage::updateOrCreate(['slug' => $page['slug']], [
                'title' => $page['title'],
                'meta_description' => $page['meta_description'],
                'is_published' => true,
                'show_in_footer' => $page['show_in_footer'] ?? false,
                'sort_order' => $order,
            ]);

            foreach ($page['sections'] as $sectionOrder => $section) {
                CmsSection::updateOrCreate(
                    ['page_id' => $model->id, 'section_key' => $section['section_key']],
                    $section + ['sort_order' => $sectionOrder, 'is_active' => true]
                );
            }
        }

        foreach (BlogContent::posts() as $index => $post) {
            BlogPost::updateOrCreate(['slug' => $post['slug']], $post + [
                'author_name' => 'EpickTours Editorial',
                'is_published' => true,
                // Newest first in the order listed
                'published_at' => now()->subDays($index * 3),
            ]);
        }

        Cms::forgetSettings();
        \Illuminate\Support\Facades\Cache::forget('cms.footer_pages');
    }

    public static function settings(): array
    {
        return [
            'site_name' => 'EpickTours',
            'tagline' => 'Stays across Sri Lanka',
            'meta_description' => 'Book hotels, villas, guesthouses and boutique stays across Sri Lanka with EpickTours. Local rates, clear cancellation policies, and pay online or at the property.',
            'contact_email' => '',
            'contact_phone' => '',
            'whatsapp' => '',
            'address' => "Colombo\nSri Lanka",
            'office_hours' => 'Monday to Saturday, 8.30 am to 6.00 pm (Sri Lanka time)',
            'facebook_url' => '',
            'instagram_url' => '',
            'tripadvisor_url' => '',
            'youtube_url' => '',
            'footer_about' => 'EpickTours connects travellers with hotels, villas and guesthouses across Sri Lanka, from the beaches of the south coast to the tea country and the ancient cities.',
            'copyright' => 'EpickTours. All rights reserved.',
        ];
    }

    private static function pages(): array
    {
        return [
            [
                'slug' => 'home',
                'title' => 'Stays across Sri Lanka',
                'meta_description' => 'Find and book stays across Sri Lanka: beach villas, hill-country bungalows, boutique hotels and family guesthouses. Clear prices in LKR or USD.',
                'sections' => [
                    [
                        'section_key' => 'hero',
                        'label' => 'Banner',
                        'heading' => 'Discover Sri Lanka',
                        'subheading' => 'Stay where the island comes alive',
                        'image_path' => 'theme/banner-image.png',
                    ],
                    [
                        'section_key' => 'about',
                        'label' => 'Introduction',
                        'subheading' => 'Welcome to EpickTours',
                        'heading' => 'Your gateway to the island of serendipity',
                        'body' => "Sri Lanka packs a whole continent's worth of experiences into one teardrop-shaped island. In a single trip you can climb a 5th-century rock fortress at sunrise, ride a train through emerald tea estates, watch leopards in the dry-zone jungle and end the day on a palm-fringed beach.\n\n"
                            ."EpickTours brings together the places to stay that make those journeys special: family-run guesthouses with home-cooked rice and curry, colonial-era bungalows in the hills, boutique hotels in old Galle and quiet villas by the sea. Every property is managed by its owner, with prices, availability and cancellation policies shown clearly before you book.",
                        'image_path' => 'theme/about-image-1.jpg',
                        'button_text' => 'More about EpickTours',
                        'button_url' => '/about',
                    ],
                    [
                        'section_key' => 'destinations',
                        'label' => 'Popular destinations',
                        'subheading' => 'Popular destinations',
                        'heading' => 'Where travellers fall in love with Sri Lanka',
                        'items' => [
                            ['title' => 'Sigiriya & Dambulla', 'text' => 'The Lion Rock fortress and the painted cave temples of the Cultural Triangle.', 'image_path' => 'theme/popular-destination-1.jpg', 'url' => '/search?area=Matale', 'is_active' => true],
                            ['title' => 'Kandy', 'text' => 'The hill capital, home of the Temple of the Sacred Tooth Relic and the Esala Perahera.', 'image_path' => 'theme/popular-destination-2.jpg', 'url' => '/search?area=Kandy', 'is_active' => true],
                            ['title' => 'Galle', 'text' => 'Cobbled lanes, boutique stays and sunset walks on the ramparts of a UNESCO-listed fort.', 'image_path' => 'theme/popular-destination-3.jpg', 'url' => '/search?area=Galle', 'is_active' => true],
                            ['title' => 'Ella', 'text' => 'Mountain views, the Nine Arches Bridge and easy hikes to Little Adam\'s Peak.', 'image_path' => 'theme/popular-destination-4.jpg', 'url' => '/search?area=Badulla', 'is_active' => true],
                            ['title' => 'Nuwara Eliya', 'text' => 'Cool air, tea estates and red-brick bungalows in "Little England".', 'image_path' => 'theme/popular-destination-5.jpg', 'url' => '/search?area=Nuwara Eliya', 'is_active' => true],
                            ['title' => 'Yala', 'text' => 'Safari country, famous for leopards, elephants and sloth bears.', 'image_path' => 'theme/popular-destination-6.jpg', 'url' => '/search?area=Hambantota', 'is_active' => true],
                            ['title' => 'Mirissa', 'text' => 'Golden bays, surf breaks and blue-whale watching in season.', 'image_path' => 'theme/popular-destination-7.jpg', 'url' => '/search?area=Matara', 'is_active' => true],
                            ['title' => 'Anuradhapura', 'text' => 'Ancient capital of great stupas and the sacred Sri Maha Bodhi tree.', 'image_path' => 'theme/popular-destination-8.jpg', 'url' => '/search?area=Anuradhapura', 'is_active' => true],
                        ],
                    ],
                    [
                        'section_key' => 'featured',
                        'label' => 'Featured stays',
                        'subheading' => 'Featured stays',
                        'heading' => 'Handpicked places to stay',
                        'body' => 'A few of the properties guests are booking right now. Prices update with your dates.',
                        'button_text' => 'See all stays',
                        'button_url' => '/search',
                    ],
                    [
                        'section_key' => 'why',
                        'label' => 'Why book with us',
                        'subheading' => 'Travel with confidence',
                        'heading' => 'Why book with EpickTours',
                        'items' => [
                            ['title' => 'Local properties, local knowledge', 'text' => 'Every stay is run by its owner in Sri Lanka, so you get genuine hospitality and honest local advice.', 'is_active' => true],
                            ['title' => 'Clear prices', 'text' => 'See the full price for your dates and meal plan before you book, in rupees or US dollars.', 'is_active' => true],
                            ['title' => 'Know your cancellation terms', 'text' => 'Each property shows its free-cancellation date up front, and you can cancel online from your booking page.', 'is_active' => true],
                            ['title' => 'Pay your way', 'text' => 'Pay securely online when you book, or choose to pay at the property when you arrive.', 'is_active' => true],
                        ],
                    ],
                    [
                        'section_key' => 'owners',
                        'label' => 'Property owners',
                        'subheading' => 'For property owners',
                        'heading' => 'Own a hotel, villa or guesthouse in Sri Lanka?',
                        'body' => 'List it on EpickTours and reach travellers planning their trip to the island. Set your own rates in rupees and dollars, manage rooms and availability, and receive bookings with no set-up fee.',
                        'button_text' => 'List your property',
                        'button_url' => '/signup',
                    ],
                    [
                        'section_key' => 'blog',
                        'label' => 'Blog',
                        'subheading' => 'Travel journal',
                        'heading' => 'Stories from the island',
                        'button_text' => 'Read the travel journal',
                        'button_url' => '/blog',
                    ],
                ],
            ],
            [
                'slug' => 'about',
                'title' => 'About EpickTours',
                'meta_description' => 'EpickTours is a booking platform for Sri Lankan hotels, villas and guesthouses, built to bring more travellers to the island and to its local hosts.',
                'sections' => [
                    [
                        'section_key' => 'intro',
                        'label' => 'Introduction',
                        'heading' => 'About EpickTours',
                        'subheading' => 'Made in Sri Lanka, for everyone who wants to discover it',
                        'body' => "EpickTours is a booking platform dedicated to one destination: Sri Lanka. We believe this island deserves to be explored slowly and widely, beyond a handful of famous stops, and that the best way to do it is to stay with the people who know it best.\n\n"
                            ."We bring together hotels, villas, guesthouses and boutique stays from every corner of the island, so you can plan a whole journey in one place: a heritage hotel near the ancient cities, a tea-estate bungalow in the hills, a safari lodge near Yala and a beach villa to finish.",
                        'image_path' => 'theme/about-image-2.jpg',
                    ],
                    [
                        'section_key' => 'mission',
                        'label' => 'Our mission',
                        'heading' => 'Our mission',
                        'body' => "To grow tourism to Sri Lanka in a way that benefits the whole island.\n\n"
                            ."When you book through EpickTours, your money goes to locally owned properties, from large hotels to small family guesthouses. We help hosts of every size reach travellers from around the world, and we help travellers find honest prices, clear policies and the warm welcome Sri Lanka is famous for.",
                    ],
                    [
                        'section_key' => 'travellers',
                        'label' => 'For travellers',
                        'heading' => 'What we promise travellers',
                        'items' => [
                            ['title' => 'Transparent prices', 'text' => 'The price you see for your dates, rooms and meal plan is the price you pay.', 'is_active' => true],
                            ['title' => 'Clear cancellation terms', 'text' => 'Every property shows exactly until when you can cancel for free.', 'is_active' => true],
                            ['title' => 'Flexible payment', 'text' => 'Pay online when you book or at the property, whichever suits you.', 'is_active' => true],
                            ['title' => 'Help when you need it', 'text' => 'Our team and our virtual receptionist are here to answer your questions.', 'is_active' => true],
                        ],
                    ],
                    [
                        'section_key' => 'owners',
                        'label' => 'For property owners',
                        'heading' => 'Built with Sri Lankan hosts',
                        'body' => "EpickTours gives property owners simple tools to run their online bookings: rates calendars in rupees and dollars, room inventory, staff accounts, payments and monthly income reports. Owners stay in control of their prices and policies, and we focus on bringing them guests.",
                        'button_text' => 'List your property',
                        'button_url' => '/signup',
                    ],
                ],
            ],
            [
                'slug' => 'contact',
                'title' => 'Contact us',
                'meta_description' => 'Contact the EpickTours team about bookings, listing your property or partnerships.',
                'sections' => [
                    [
                        'section_key' => 'intro',
                        'label' => 'Introduction',
                        'heading' => 'Get in touch',
                        'subheading' => 'We are happy to help',
                        'body' => "Have a question about a booking, want to list your property, or need help planning your stay in Sri Lanka? Send us a message and our team will reply as soon as possible, usually within one working day.\n\n"
                            ."**Already booked?** You can view, pay for or cancel your booking yourself with your booking reference and email on the [Find my booking](/booking/find) page.",
                    ],
                ],
            ],
            [
                'slug' => 'blog',
                'title' => 'Travel journal',
                'meta_description' => 'Stories, guides and travel tips about Sri Lanka: heritage, wildlife, seasons, culture and the island\'s famous hospitality.',
                'sections' => [
                    [
                        'section_key' => 'intro',
                        'label' => 'Introduction',
                        'heading' => 'The EpickTours travel journal',
                        'subheading' => 'Stories and guides to help you discover Sri Lanka',
                    ],
                ],
            ],
            [
                'slug' => 'faq',
                'title' => 'Frequently asked questions',
                'meta_description' => 'Answers to common questions about booking, paying and cancelling stays on EpickTours.',
                'show_in_footer' => true,
                'sections' => [['section_key' => 'body', 'label' => 'Content', 'heading' => 'Frequently asked questions', 'body' => LegalContent::faq()]],
            ],
            [
                'slug' => 'terms',
                'title' => 'Terms of use',
                'meta_description' => 'The terms that apply when you use the EpickTours website and book a stay.',
                'show_in_footer' => true,
                'sections' => [['section_key' => 'body', 'label' => 'Content', 'heading' => 'Terms of use', 'body' => LegalContent::terms()]],
            ],
            [
                'slug' => 'privacy',
                'title' => 'Privacy policy',
                'meta_description' => 'How EpickTours collects, uses and protects your personal information.',
                'show_in_footer' => true,
                'sections' => [['section_key' => 'body', 'label' => 'Content', 'heading' => 'Privacy policy', 'body' => LegalContent::privacy()]],
            ],
            [
                'slug' => 'cookies',
                'title' => 'Cookie policy',
                'meta_description' => 'How the EpickTours website uses cookies.',
                'show_in_footer' => true,
                'sections' => [['section_key' => 'body', 'label' => 'Content', 'heading' => 'Cookie policy', 'body' => LegalContent::cookies()]],
            ],
        ];
    }
}
