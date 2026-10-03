<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 20)->unique();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('total_days')->comment('Jumlah hari sewa, minimum 1');
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->string('payment_method', 20)->comment('cash | qris | bank_transfer');
            $table->string('payment_status', 25)->default('belum_dibayar');
            $table->string('booking_status', 25)->default('menunggu_konfirmasi');
            $table->unsignedSmallInteger('renter_count')->nullable()->comment('Jumlah penyewa, relevan untuk sewa sepeda');
            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();

            // Index utama untuk pengecekan ketersediaan (BR-04): menyaring
            // booking aktif yang periode-nya beririsan dengan periode pencarian.
            $table->index(['business_id', 'booking_status', 'start_date', 'end_date'], 'bookings_availability_index');
            $table->index(['business_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
