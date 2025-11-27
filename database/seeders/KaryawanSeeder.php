<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;     // Pastikan import User
use App\Models\Karyawan; // Pastikan import Karyawan
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KaryawanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // --- AMBIL ID POSISI & DIVISI ---
        $divisiItId = DB::table('divisis')->where('nama', 'IT')->value('id');
        $posisiManagerId = DB::table('posisis')->where('nama', 'Manager')->value('id');
        $posisiStaffId = DB::table('posisis')->where('nama', 'Staff')->value('id');

        // Fallback jika data master belum ada (untuk safety)
        if (!$divisiItId) $divisiItId = 1;
        if (!$posisiManagerId) $posisiManagerId = 1;
        if (!$posisiStaffId) $posisiStaffId = 2;

        // ==========================================
        // 1. BUAT AKUN ADMIN (MANAGER)
        // ==========================================
        
        // A. Buat User Login Dulu
        $adminUser = User::create([
            'name' => 'Admin Absensi',
            'email' => 'admin@kantor.com',
            'password' => Hash::make('password123'),
        ]);

        // B. Buat Data Karyawan (Link ke User)
        Karyawan::create([
            'user_id' => $adminUser->id, // <--- Wajib ada
            'nama' => 'Admin Absensi',
            'email' => 'admin@kantor.com',
            'password' => Hash::make('password123'),
            'divisi_id' => $divisiItId,
            'posisi_id' => $posisiManagerId, 
            'status_kerja' => 'tetap',
            'tanggal_masuk' => now(),
            'sisa_cuti' => 12
        ]);
        
        $this->command->info('Akun Admin berhasil dibuat.');


        // ==========================================
        // 2. BUAT AKUN KARYAWAN BIASA (USER)
        // ==========================================

        // A. Buat User Login Dulu (INI YANG TADI KURANG)
        $staffUser = User::create([
            'name' => 'User Karyawan',
            'email' => 'user@kantor.com',
            'password' => Hash::make('password123'),
        ]);

        // B. Buat Data Karyawan (Link ke User)
        Karyawan::create([
            'user_id' => $staffUser->id, // <--- Wajib ada
            'nama' => 'User Karyawan',
            'email' => 'user@kantor.com',
            'password' => Hash::make('password123'),
            'divisi_id' => $divisiItId,
            'posisi_id' => $posisiStaffId,
            'status_kerja' => 'tetap',
            'tanggal_masuk' => Carbon::today()->subMonths(1),
            'sisa_cuti' => 12
        ]);
        
        $this->command->info('Akun Karyawan Biasa berhasil dibuat.');
    }
}