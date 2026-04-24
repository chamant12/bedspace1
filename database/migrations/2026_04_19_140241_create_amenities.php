<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('amenity',255);
            $table->enum('amenity_type',['roomType','property'])->default('property');
            $table->timestamps();
        });
        // Infrastructure & General
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('100% Power Backup (Generator)','property')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Driver Accommodation & Meals','property')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('24-Hour Security & CCTV','property')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Doctor on Call','property')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('EV Charging Point','property')");

// Wellness & Leisure
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Ayurvedic Spa & Wellness Centre','property')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Infinity Swimming Pool','property')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Fitness Centre / Gym','property')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Kids Play Area','property')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Yoga Pavilion','property')");

// Food & Beverage
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Main Restaurant (International & Local)','property')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Licensed Bar & Lounge','property')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('BBQ & Outdoor Dining Facilities','property')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Coffee Shop','property')");

// Services & Logistics
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Travel & Tour Desk','property')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Foreign Currency Exchange','property')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('In-house Laundry & Dry Cleaning','property')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Business Centre & Meeting Rooms','property')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Airport Shuttle Service','property')");

// Entertainment
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Free Property-wide Wi-Fi','property')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Library / Reading Room','property')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Games Room (Carrom, Billiards)','property')");

// Climate & Comfort
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Individual Climate Control (AC)','roomType')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Ceiling Fan','roomType')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Mosquito Net / Electric Repellent','roomType')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Blackout Curtains','roomType')");

// Refreshments (The Sri Lankan Standard)
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Ceylon Tea & Coffee Making Facilities','roomType')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Minibar / Mini Fridge','roomType')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Complimentary Bottled Water','roomType')");

// Technology & Entertainment
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('High-speed Wi-Fi','roomType')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Flat Screen TV with Satellite Channels','roomType')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('International Plug Sockets (Universal)','roomType')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Electronic Safety Deposit Box','roomType')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('IDD Telephone Facility','roomType')");

// Bathroom & Personal Care
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('En-suite Bathroom with Rain Shower','roomType')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Luxury Toiletries (Ayurvedic/Local)','roomType')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Hair Dryer','roomType')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Bathrobes & Slippers','roomType')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Hot & Cold Water','roomType')");

// Furniture & View
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Private Balcony / Terrace','roomType')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Work Desk & Chair','roomType')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Iron & Ironing Board','roomType')");
DB::statement("INSERT INTO amenities (amenity,amenity_type) VALUES ('Wardrobe / Closet','roomType')");



        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amenities');
    }
};
