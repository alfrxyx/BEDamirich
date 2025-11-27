<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PosisiSeeder extends Seeder
{
    public function run(): void
    {
        $posisiData = [
            ['nama' => 'Programmer'],
            ['nama' => 'IT Support'],
            ['nama' => 'HR Staff'],
            ['nama' => 'Manager'],
            ['nama' => 'Marketing Coordinator'],
            ['nama' => 'Sales Executive'],
            ['nama' => 'Finance Officer'],
        ];

        DB::table('posisis')->insert($posisiData);
    }
}