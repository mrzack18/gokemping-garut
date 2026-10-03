<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->string('type', 20)->comment('cash | qris | bank_transfer');
            $table->string('merchant_name')->nullable()->comment('Nama merchant QRIS');
            $table->string('qris_image')->nullable()->comment('Path gambar QRIS pada disk public');
            $table->string('bank_name')->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('account_name')->nullable();
            $table->text('instructions')->nullable()->comment('Keterangan pembayaran, mis. untuk cash');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Satu konfigurasi per metode per unit bisnis.
            $table->unique(['business_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
