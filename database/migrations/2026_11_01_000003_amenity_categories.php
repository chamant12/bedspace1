<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Amenities get a category (groups in config/bedspace.php, amenity_categories)
     * and a sort order. Room amenities are replaced by an Expedia-style list:
     * existing room amenities are renamed to their equivalent in the new list,
     * so room types that already have them keep them; the rest are added.
     */
    private const ROOM = [
        'bedroom' => ['Premium bedding', 'Hypo-allergenic bedding', 'Down comforter', 'Pillowtop mattress', 'Pillow menu',
            'Blackout drapes/curtains', 'Linens provided', 'Wardrobe or closet', 'Rollaway/extra beds available', 'Cribs/infant beds available'],
        'bathroom' => ['Private bathroom', 'Shower only', 'Bathtub or shower', 'Separate bathtub and shower', 'Rainfall showerhead',
            'Hot water', 'Bidet', 'Free toiletries', 'Designer toiletries', 'Hair dryer', 'Bathrobes', 'Slippers', 'Towels provided'],
        'entertainment' => ['Flat-screen TV', 'Satellite TV channels', 'Cable TV channels', 'Smart TV', 'Streaming services', 'Bluetooth speaker'],
        'food' => ['Coffee/tea maker', 'Electric kettle', 'Minibar', 'Refrigerator', 'Microwave', 'Kitchenette', 'Free bottled water',
            'Room service', '24-hour room service', 'Dining area'],
        'internet' => ['Free WiFi', 'Free wired internet'],
        'comfort' => ['Air conditioning', 'Ceiling fan', 'Climate control', 'Heating', 'Mosquito net', 'Soundproofed rooms'],
        'practical' => ['In-room safe', 'Desk', 'Iron/ironing board', 'Phone', 'Free local calls', 'Universal power outlets',
            'USB charging ports', 'Daily housekeeping', 'Wake-up calls', 'Connecting/adjoining rooms available', 'Non-smoking'],
        'outdoor' => ['Balcony', 'Private patio/terrace', 'Private pool', 'Plunge pool', 'Sea view', 'Beach view', 'Garden view',
            'Mountain view', 'Pool view', 'Lake view', 'City view'],
        'accessibility' => ['Wheelchair accessible', 'Accessible bathroom', 'Roll-in shower', 'Grab bars in bathroom', 'Lowered sink', 'Visual alarm'],
    ];

    // Original room amenities -> their name in the new list
    private const RENAME = [
        'Individual Climate Control (AC)' => 'Air conditioning',
        'Ceiling Fan' => 'Ceiling fan',
        'Mosquito Net / Electric Repellent' => 'Mosquito net',
        'Blackout Curtains' => 'Blackout drapes/curtains',
        'Ceylon Tea & Coffee Making Facilities' => 'Coffee/tea maker',
        'Minibar / Mini Fridge' => 'Minibar',
        'Complimentary Bottled Water' => 'Free bottled water',
        'High-speed Wi-Fi' => 'Free WiFi',
        'Flat Screen TV with Satellite Channels' => 'Flat-screen TV',
        'International Plug Sockets (Universal)' => 'Universal power outlets',
        'Electronic Safety Deposit Box' => 'In-room safe',
        'IDD Telephone Facility' => 'Phone',
        'En-suite Bathroom with Rain Shower' => 'Private bathroom',
        'Luxury Toiletries (Ayurvedic/Local)' => 'Designer toiletries',
        'Hair Dryer' => 'Hair dryer',
        'Bathrobes & Slippers' => 'Bathrobes',
        'Hot & Cold Water' => 'Hot water',
        'Private Balcony / Terrace' => 'Balcony',
        'Work Desk & Chair' => 'Desk',
        'Iron & Ironing Board' => 'Iron/ironing board',
        'Wardrobe / Closet' => 'Wardrobe or closet',
    ];

    // Original property amenities, grouped
    private const PROPERTY = [
        'general' => ['100% Power Backup (Generator)', 'Driver Accommodation & Meals', '24-Hour Security & CCTV', 'Doctor on Call', 'EV Charging Point'],
        'wellness' => ['Ayurvedic Spa & Wellness Centre', 'Infinity Swimming Pool', 'Fitness Centre / Gym', 'Kids Play Area', 'Yoga Pavilion'],
        'food' => ['Main Restaurant (International & Local)', 'Licensed Bar & Lounge', 'BBQ & Outdoor Dining Facilities', 'Coffee Shop'],
        'services' => ['Travel & Tour Desk', 'Foreign Currency Exchange', 'In-house Laundry & Dry Cleaning', 'Business Centre & Meeting Rooms', 'Airport Shuttle Service'],
        'internet' => ['Free Property-wide Wi-Fi', 'Library / Reading Room', 'Games Room (Carrom, Billiards)'],
    ];

    public function up(): void
    {
        Schema::table('amenities', function (Blueprint $table) {
            $table->string('category', 30)->nullable()->after('amenity_type');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('category');
        });

        foreach (self::RENAME as $old => $new) {
            $exists = DB::table('amenities')->where(['amenity_type' => 'roomType', 'amenity' => $new])->exists();
            if (!$exists) {
                DB::table('amenities')->where(['amenity_type' => 'roomType', 'amenity' => $old])->update(['amenity' => $new]);
            }
        }

        $position = 0;
        foreach (self::ROOM as $category => $names) {
            foreach ($names as $name) {
                $position++;
                $existing = DB::table('amenities')->where(['amenity_type' => 'roomType', 'amenity' => $name]);
                if ($existing->exists()) {
                    $existing->update(['category' => $category, 'sort_order' => $position, 'updated_at' => now()]);
                } else {
                    DB::table('amenities')->insert([
                        'amenity' => $name, 'amenity_type' => 'roomType', 'category' => $category, 'sort_order' => $position,
                        'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        }

        $position = 0;
        foreach (self::PROPERTY as $category => $names) {
            foreach ($names as $name) {
                $position++;
                DB::table('amenities')->where(['amenity_type' => 'property', 'amenity' => $name])
                    ->update(['category' => $category, 'sort_order' => $position]);
            }
        }

        // Anything else (added by admins) goes under "Other"
        DB::table('amenities')->whereNull('category')->update(['category' => 'other', 'sort_order' => 999]);
    }

    public function down(): void
    {
        Schema::table('amenities', function (Blueprint $table) {
            $table->dropColumn(['category', 'sort_order']);
        });
    }
};
