<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->string('method', 20)->comment('cash | qris | bank_transfer');
            $table->unsignedBigInteger('amount');
            $table->string('proof')->nullable()->comment('Path bukti bayar pada disk public');
            $table->string('status', 25)->default('belum_dibayar')
                ->comment('belum_dibayar | menunggu_verifikasi | lunas | ditolak');
            $table->string('rejection_reason')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
