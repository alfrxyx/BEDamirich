<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DivisiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Data divisi yang ingin Anda masukkan
        // Perhatikan bahwa nama kolom adalah 'nama', bukan 'nama_divisi'
        $divisiData = [
            ['nama' => 'IT'],
            ['nama' => 'HR'],
            ['nama' => 'Finance'],
            ['nama' => 'Marketing'],
            ['nama' => 'Sales'],
            ['nama' => 'Operational'],
            // Tambahkan divisi lain sesuai kebutuhan
        ];

        // Masukkan data ke tabel 'divisis'
        DB::table('divisis')->insert($divisiData);
    }
}