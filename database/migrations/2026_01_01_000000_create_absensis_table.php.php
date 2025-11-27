<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('absensis', function (Blueprint $table) {
            $table->id();
            
            // --- RELASI KE USER (WAJIB) ---
            // Kita pakai user_id sebagai kunci utama pelacakan
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // --- RELASI KE KARYAWAN (OPSIONAL) ---
            // Boleh kosong (nullable) jika data karyawan belum lengkap
            $table->foreignId('karyawan_id')->nullable()->constrained('karyawans')->onDelete('cascade');

            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            
            $table->string('status')->default('hadir'); // hadir, terlambat, izin, sakit
            $table->string('metode')->nullable(); // qr, selfie, manual
            
            // --- KOORDINAT LOKASI ---
            // Gunakan decimal untuk presisi GPS yang lebih baik daripada string
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // --- FOTO BUKTI ---
            $table->string('foto_masuk')->nullable();
            $table->string('foto_pulang')->nullable();
            
            $table->text('keterangan')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensis');
    }
};