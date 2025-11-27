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
        // PENTING: Nama tabel HARUS 'attendance_tokens'
        Schema::create('attendance_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique(); // Token unik untuk di-scan
            $table->timestamp('expires_at');    // Waktu kedaluwarsa
            $table->boolean('is_active')->default(true); // Status aktif (hanya 1 yang aktif)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_tokens');
    }
};