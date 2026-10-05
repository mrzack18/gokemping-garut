<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            // Status sebelum perubahan. Dikosongkan untuk baris pertama, yaitu
            // saat booking dibuat, karena belum ada status sebelumnya.
            $table->string('from_status', 25)->nullable();
            $table->string('to_status', 25);
            // Alasan pembatalan atau catatan admin. Hanya diisi untuk perubahan
            // ke `dibatalkan`.
            $table->string('note', 200)->nullable();
            // Admin yang melakukan perubahan. Dikosongkan untuk baris pertama
            // karena booking dibuat oleh penyewa lewat halaman publik, bukan oleh
            // admin.
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['booking_id', 'id']);
        });

        // Booking yang sudah ada sebelum tabel ini dibuat belum punya riwayat.
        // Satu baris dissentilkan per booking supaya halaman detail tidak
        // menampilkan riwayat kosong untuk booking lama, dan supaya admin tetap
        // bisa melihat status booking itu tanpa mencari tahu dari mana asalnya.
        //
        // `from_status` sengaja dikosongkan. Kita tahu status akhirnya, tapi tidak
        // tahu status mana yang dilalui sebelum tabel ini ada, jadi mengarang
        // status sebelumnya akan membuat riwayat terlihat lebih lengkap
        // daripada kenyataannya.
        DB::statement(
            'insert into booking_status_histories'
            .' (booking_id, from_status, to_status, note, changed_by, created_at, updated_at)'
            .' select id, null, booking_status, ?, null, created_at, created_at from bookings',
            ['Riwayat status belum direkam sebelum halaman ini dibuat.'],
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_status_histories');
    }
};
