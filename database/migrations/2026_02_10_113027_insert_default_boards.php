<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ambil semua project yang ada
        $projects = DB::table('projects')->pluck('project_id');

        foreach ($projects as $projectId) {
            // Cek apakah board sudah ada untuk project ini
            $existingBoards = DB::table('boards')
                ->where('project_id', $projectId)
                ->pluck('nama_board')
                ->toArray();

            // Daftar board default
            $defaultBoards = ['To Do', 'Doing', 'Done'];

            foreach ($defaultBoards as $boardName) {
                // Hanya insert jika belum ada
                if (!in_array($boardName, $existingBoards)) {
                    DB::table('boards')->insert([
                        'project_id' => $projectId,
                        'nama_board' => $boardName,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Opsional: hapus hanya board default
        DB::table('boards')
            ->whereIn('nama_board', ['To Do', 'Doing', 'Done'])
            ->delete();
    }
};