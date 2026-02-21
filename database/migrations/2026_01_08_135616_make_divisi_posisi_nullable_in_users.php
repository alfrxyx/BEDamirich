<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Ubah divisi_id & posisi_id jadi nullable
            $table->unsignedBigInteger('divisi_id')->nullable()->change();
            $table->unsignedBigInteger('posisi_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('divisi_id')->nullable(false)->change();
            $table->unsignedBigInteger('posisi_id')->nullable(false)->change();
        });
    }
};