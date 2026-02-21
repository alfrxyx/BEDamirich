<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            Schema::create('boards', function (Blueprint $table) {
                $table->id('board_id'); // auto-increment BIGINT
                $table->foreignId('project_id')
                    ->constrained('projects', 'project_id')
                    ->onDelete('cascade');
                $table->string('nama_board');
                $table->timestamps();
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('boards');
    }
};