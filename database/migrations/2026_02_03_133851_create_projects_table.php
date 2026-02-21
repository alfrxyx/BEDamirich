<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id('project_id'); // Primary key custom
            $table->string('nama_project');
            $table->text('deskripsi')->nullable();
            $table->string('divisi');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // ✅ BENAR
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('status')->default('Aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};