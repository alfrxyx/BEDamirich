<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bug_reports', function (Blueprint $table) {
            $table->id('bug_id');
            $table->foreignId('project_id')->constrained('projects', 'project_id')->onDelete('cascade');
            $table->foreignId('task_id')->nullable()->constrained('tasks', 'task_id')->onDelete('set null');
            $table->foreignId('user_id')->constrained('users', 'id')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->enum('priority', ['Low', 'Medium', 'High', 'Critical']);
            $table->enum('severity', ['Minor', 'Major', 'Critical']);
            $table->enum('status', ['Open', 'In Progress', 'Resolved', 'Closed'])->default('Open');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bug_reports');
    }
};