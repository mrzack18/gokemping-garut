<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // Kontak tambahan di luar WhatsApp dan email bawaan.
            $table->string('phone', 25)->nullable()->after('whatsapp');
            // Informasi layanan yang tampil di halaman pilih layanan.
            $table->text('service_intro')->nullable()->after('description');
            $table->json('service_highlights')->nullable()->after('service_intro');
            // Ketentuan sewa umum unit, dipisahkan dari ketentuan per produk.
            $table->text('rental_terms')->nullable()->after('service_highlights');
            // URL embed Google Maps untuk section lokasi di landing page.
            $table->string('maps_embed_url', 2048)->nullable()->after('address');
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->string('subtitle')->nullable();
            $table->string('image')->comment('Path gambar banner pada disk public');
            $table->string('link_url', 2048)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'is_active', 'sort_order']);
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('question');
            $table->text('answer');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('banners');

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'service_intro',
                'service_highlights',
                'rental_terms',
                'maps_embed_url',
            ]);
        });
    }
};
