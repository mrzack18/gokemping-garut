<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            // Nullable karena produk bisa dihapus, sementara BR-09 mewajibkan
            // nilai transaksi lama tetap terbaca apa adanya.
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name')->comment('Snapshot nama produk saat transaksi dibuat');
            $table->string('price_unit', 20)->comment('Snapshot satuan harga');
            $table->unsignedBigInteger('price')->comment('Snapshot harga per satuan');
            $table->unsignedInteger('quantity');
            $table->unsignedSmallInteger('total_days')->comment('Snapshot durasi sewa item ini');
            $table->unsignedBigInteger('subtotal')->comment('price x quantity x total_days');
            $table->timestamps();

            $table->index(['booking_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};
