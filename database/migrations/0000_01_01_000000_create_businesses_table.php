<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration ini diberi awalan 0000 (setelah 0001_01_01_000000_create_users_table)
     * ada kolom business_id, sehingga tabel businesses harus dibuat lebih dulu.
     */
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('whatsapp')->comment('Nomor WhatsApp admin unit bisnis ini');
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('booking_code_prefix', 5)->comment('GK untuk GoKemping, SSG untuk Sewa Sepeda Garut');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
