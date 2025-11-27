<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('absensis', function (Blueprint $table) {
        $table->id();
        // Relasi ke tabel karyawans
        $table->foreignId('karyawan_id')->constrained('karyawans')->onDelete('cascade');
        
        $table->date('tanggal');
        $table->time('jam_masuk');
        $table->time('jam_pulang')->nullable();
        $table->string('metode'); // 'qr' atau 'selfie'
        
        // Koordinat presisi tinggi
        $table->decimal('latitude', 10, 8);
        $table->decimal('longitude', 11, 8);
        
        $table->string('foto_selfie')->nullable();
        $table->string('status')->default('hadir'); // terlambat / tepat waktu
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
