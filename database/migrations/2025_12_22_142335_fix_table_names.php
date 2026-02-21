<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Rename tabel
        Schema::rename('divisis', 'divisi');
        Schema::rename('posisis', 'posisi');
    }

    public function down()
    {
        // Kembalikan nama jika perlu rollback
        Schema::rename('divisi', 'divisis');
        Schema::rename('posisi', 'posisis');
    }
};