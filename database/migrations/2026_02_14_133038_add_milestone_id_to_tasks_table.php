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
        Schema::table('tasks', function (Blueprint $table) {
            // Menambahkan kolom milestone_id setelah board_id
            // nullable() PENTING: karena ada task yang mungkin tidak masuk milestone
            $table->unsignedBigInteger('milestone_id')->nullable()->after('board_id');
            
            // Membuat relasi (Optional tapi direkomendasikan)
            // Agar jika Milestone dihapus, milestone_id di task jadi NULL (tasknya jangan ikut kehapus)
            $table->foreign('milestone_id')
                  ->references('milestone_id')->on('milestones')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Hapus foreign key dulu (format: nama_table_nama_kolom_foreign)
            $table->dropForeign(['milestone_id']); 
            // Baru hapus kolomnya
            $table->dropColumn('milestone_id');
        });
    }
};