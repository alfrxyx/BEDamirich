<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. MEMBERSIHKAN TABEL LAMA (Opsional, biar bersih)
        // DB::table('users')->truncate();
        // DB::table('divisis')->truncate();
        // DB::table('posisis')->truncate();

        // 2. BUAT DATA DIVISI (MASTER DATA)
        // Kita pakai insertOrIgnore agar aman dijalankan berkali-kali
        DB::table('divisis')->insertOrIgnore([
            ['id' => 1, 'name' => 'IT'],
            ['id' => 2, 'name' => 'HRD'],
            ['id' => 3, 'name' => 'Marketing'],
            ['id' => 4, 'name' => 'Finance'],
        ]);

        // 3. BUAT DATA POSISI (MASTER DATA)
        DB::table('posisis')->insertOrIgnore([
            ['id' => 1, 'name' => 'Admin'],     // ID 1 WAJIB ADMIN
            ['id' => 2, 'name' => 'Staff'],     // ID 2 KARYAWAN BIASA
            ['id' => 3, 'name' => 'Manager'],
            ['id' => 4, 'name' => 'Supervisor'],
        ]);

        // 4. BUAT AKUN ADMIN (Untuk Login Dashboard Admin)
        // Cek dulu biar gak error duplicate entry
        if (!User::where('email', 'admin@kantor.com')->exists()) {
            User::create([
                'name' => 'Admin Absensi',
                'email' => 'admin@kantor.com',
                'password' => Hash::make('password123'),
                'divisi_id' => 1, // IT
                'posisi_id' => 1, // Admin (Sesuai ID di atas)
                'tanggal_masuk' => now(),
            ]);
        }

        // 5. BUAT AKUN KARYAWAN CONTOH (Untuk Login Dashboard Karyawan)
        if (!User::where('email', 'user@kantor.com')->exists()) {
            User::create([
                'name' => 'User Karyawan',
                'email' => 'user@kantor.com',
                'password' => Hash::make('password123'),
                'divisi_id' => 1, // IT
                'posisi_id' => 2, // Staff (Sesuai ID di atas)
                'tanggal_masuk' => now(),
            ]);
        }
    }
}