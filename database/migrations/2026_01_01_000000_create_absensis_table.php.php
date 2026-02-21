<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensis', function (Blueprint $table) {
            $table->id();
            
            // ✅ HANYA RELASI KE USER (TIDAK ADA KARYAWANS)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            
            $table->string('status')->default('hadir'); // hadir, terlambat, izin, sakit
            $table->string('metode')->nullable(); // qr, selfie, manual
            
            // KOORDINAT LOKASI
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // FOTO BUKTI
            $table->string('foto_masuk')->nullable();
            $table->string('foto_pulang')->nullable();
            
            $table->text('keterangan')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensis');
    }
};