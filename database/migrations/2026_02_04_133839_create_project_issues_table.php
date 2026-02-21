<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('project_issues', function (Blueprint $table) {
            $table->id('issue_id');
            $table->foreignId('project_id')->constrained('projects', 'project_id')->onDelete('cascade');
            $table->foreignId('task_id')->nullable()->constrained('tasks', 'task_id')->onDelete('set null');
            $table->string('title');
            $table->text('description');
            $table->enum('severity', ['Low', 'Medium', 'High', 'Critical']);
            $table->enum('status', ['Open', 'In Progress', 'Resolved', 'Closed'])->default('Open');
            $table->foreignId('reported_by')->constrained('users', 'id')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_issues');
    }
};