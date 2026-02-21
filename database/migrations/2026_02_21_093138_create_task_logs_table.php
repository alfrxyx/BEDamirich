<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('task_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action'); // Jenis aksi: create, update_status, update_progress
            $table->text('description'); // Keterangan log
            $table->timestamps();

            // Relasi (Opsional tapi disarankan jika struktur database mendukung)
            // $table->foreign('task_id')->references('task_id')->on('tasks')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('task_logs');
    }
};