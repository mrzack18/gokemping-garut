<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('whatsapp', 25)->comment('Nomor WhatsApp penyewa, kunci deduplikasi');
            $table->string('email')->nullable();
            $table->string('nik', 16);
            $table->string('address');
            $table->string('city')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('whatsapp');
            $table->index('nik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
