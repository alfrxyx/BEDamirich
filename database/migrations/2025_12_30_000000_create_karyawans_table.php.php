<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('karyawans', function (Blueprint $table) {
            $table->id();
            
            // --- KOLOM PENTING YANG HILANG TADI ---
            // Ini jembatan ke tabel users
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); 
            
            $table->string('nama');
            $table->string('email')->unique()->nullable(); // Email boleh null karena sudah ada di users
            $table->string('password')->nullable();       // Password boleh null karena sudah ada di users
            
            // Foreign Keys ke Divisi & Posisi
            $table->foreignId('divisi_id')->constrained('divisis')->onDelete('cascade');
            $table->foreignId('posisi_id')->constrained('posisis')->onDelete('cascade');
            
            $table->string('status_kerja')->default('tetap');
            $table->date('tanggal_masuk');
            $table->integer('sisa_cuti')->default(12);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karyawans');
    }
};