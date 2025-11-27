<?php

namespace Database\Seeders; // <--- HARUS ADA DAN BENAR

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterDataSeeder extends Seeder // <--- HARUS MasterDataSeeder
{
    public function run()
    {
        // Data Divisi
        DB::table('divisis')->insert([
            ['nama' => 'IT'],
            ['nama' => 'HRD'],
            ['nama' => 'Marketing'],
        ]);

        // Data Posisi
        DB::table('posisis')->insert([
            ['nama' => 'Manager'],
            ['nama' => 'Staff'],
            ['nama' => 'Intern'],
        ]);
    }
}