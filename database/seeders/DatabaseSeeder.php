<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; // <--- JANGAN LUPA INI
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Data Divisi
        DB::table('divisis')->insertOrIgnore([
            ['id' => 1, 'name' => 'IT'],
            ['id' => 2, 'name' => 'HRD'],
            ['id' => 3, 'name' => 'Marketing'],
            ['id' => 4, 'name' => 'Finance'],
        ]);

        // 2. Buat Data Posisi
        DB::table('posisis')->insertOrIgnore([
            ['id' => 1, 'name' => 'Admin'],
            ['id' => 2, 'name' => 'Staff'],
            ['id' => 3, 'name' => 'Manager'],
        ]);

        // 3. Buat Akun ADMIN
        if (!User::where('email', 'admin@kantor.com')->exists()) {
            User::create([
                'name' => 'Super Admin',
                'email' => 'admin@kantor.com',
                'password' => Hash::make('password123'),
                'divisi_id' => 1, 
                'posisi_id' => 1, // Admin
                'tanggal_masuk' => now(),
                'attendance_token' => (string) Str::uuid(), // <--- TOKEN UNTUK ADMIN
            ]);
        }

        // 4. Buat Akun Karyawan Contoh
        if (!User::where('email', 'user@kantor.com')->exists()) {
            User::create([
                'name' => 'Karyawan Biasa',
                'email' => 'user@kantor.com',
                'password' => Hash::make('password123'),
                'divisi_id' => 1, 
                'posisi_id' => 2, // Staff
                'tanggal_masuk' => now(),
                'attendance_token' => (string) Str::uuid(), // <--- TOKEN UNTUK KARYAWAN
            ]);
        }
    }
}