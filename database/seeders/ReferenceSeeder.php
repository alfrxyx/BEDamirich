<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReferenceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Isi Tabel Divisi
        // Kita pakai insertOrIgnore biar gak error kalau dijalankan dobel
        DB::table('divisis')->insertOrIgnore([
            ['id' => 1, 'nama' => 'IT', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'nama' => 'HRD', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'nama' => 'Finance', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'nama' => 'Marketing', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 2. Isi Tabel Posisi
        DB::table('posisis')->insertOrIgnore([
            ['id' => 1, 'nama' => 'Manager', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'nama' => 'Staff', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'nama' => 'Intern', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}