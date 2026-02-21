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
    // database/migrations/..._create_tasks_table.php
    {
    Schema::create('task_assignees', function (Blueprint $table) {
        $table->foreignId('task_id')->constrained('tasks', 'task_id')->onDelete('cascade');
        $table->foreignId('user_id')->constrained('users', 'id')->onDelete('cascade');
        $table->primary(['task_id', 'user_id']); // kunci gabungan
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_assignees');
    }
};
