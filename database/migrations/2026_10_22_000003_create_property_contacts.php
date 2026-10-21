<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Moves the property's contact person out of the properties table into
     * its own record. A contact may later be given a login (user_id).
     */
    public function up(): void
    {
        Schema::create('property_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('mobile', 50);
            $table->string('email', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_primary')->default(true);
            $table->timestamps();

            $table->index(['property_id', 'is_primary']);
        });

        DB::table('properties')->orderBy('id')->each(function ($property) {
            DB::table('property_contacts')->insert([
                'property_id' => $property->id,
                'name' => $property->contact_name,
                'mobile' => $property->contact_number,
                'email' => $property->contact_email ?: null,
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['contact_name', 'contact_number', 'contact_email']);
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('contact_name', 255)->default('');
            $table->string('contact_number', 255)->default('');
            $table->string('contact_email', 255)->default('');
        });

        DB::table('property_contacts')->where('is_primary', true)->orderBy('id')->each(function ($contact) {
            DB::table('properties')->where('id', $contact->property_id)->update([
                'contact_name' => $contact->name,
                'contact_number' => $contact->mobile,
                'contact_email' => $contact->email ?? '',
            ]);
        });

        Schema::dropIfExists('property_contacts');
    }
};
