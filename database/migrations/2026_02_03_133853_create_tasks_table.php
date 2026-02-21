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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id('task_id');
            $table->foreignId('board_id')->constrained('boards', 'board_id')->onDelete('cascade');
            $table->string('judul');
            $table->text('deskripsi')->nullable(); // Pastikan konsisten (deskripsi/description)
            $table->date('due_date');
            
            // KOLOM BARU UNTUK KANBAN
            $table->enum('status', ['not_started', 'in_progress', 'done'])->default('not_started');
            $table->enum('prioritas', ['Low', 'Medium', 'High', 'Urgent'])->default('Medium');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};