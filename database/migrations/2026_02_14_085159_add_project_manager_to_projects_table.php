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
            Schema::table('projects', function (Blueprint $table) {
                $table->unsignedBigInteger('project_manager_id')->nullable()->after('created_by');
                // $table->foreign('project_manager_id')->references('id')->on('users'); // Opsional relation
            });
        }

        public function down()
        {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('project_manager_id');
            });
        }
};
