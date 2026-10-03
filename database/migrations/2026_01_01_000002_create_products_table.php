<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->json('specification')->nullable()->comment('Spesifikasi bebas dalam bentuk objek JSON');
            $table->text('rental_terms')->nullable()->comment('Ketentuan penyewaan');
            $table->unsignedBigInteger('price')->comment('Harga sewa per satuan, dalam rupiah penuh');
            $table->string('price_unit', 20)->default('hari')->comment('Satuan harga, mis. hari / jam / paket');
            $table->unsignedInteger('stock')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'slug']);
            $table->index(['business_id', 'is_active']);
            $table->index(['business_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
