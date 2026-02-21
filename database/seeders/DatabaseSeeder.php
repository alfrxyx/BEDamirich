<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 🔥 HAPUS DATA DALAM URUTAN YANG BENAR (CHILD → PARENT)
        DB::table('absensis')->delete();
        DB::table('leave_requests')->delete();
        DB::table('notifications')->delete();
        DB::table('documents')->delete();
        DB::table('users')->delete();
        DB::table('divisi')->delete();
        DB::table('posisi')->delete();

        // 🔥 RESET AUTO INCREMENT
        DB::statement('ALTER TABLE users AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE divisi AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE posisi AUTO_INCREMENT = 1');

        // 1. ISI TABEL DIVISI
        DB::table('divisi')->insert([
            ['id' => 1, 'name' => 'Shareholder'],
            ['id' => 2, 'name' => 'Operation'],
            ['id' => 3, 'name' => 'Consultant'],
            ['id' => 4, 'name' => 'Associate'],
            ['id' => 5, 'name' => 'Development'],
        ]);

        // 2. ISI TABEL POSISI
        DB::table('posisi')->insert([
            ['id' => 1, 'name' => 'Commissioner'],
            ['id' => 2, 'name' => 'CEO'],
            ['id' => 3, 'name' => 'COO'],
            ['id' => 4, 'name' => 'Manager of Operation'],
            ['id' => 5, 'name' => 'Senior Consultant'],
            ['id' => 6, 'name' => 'Senior Expert Associate'],
            ['id' => 7, 'name' => 'Senior Associate'],
            ['id' => 8, 'name' => 'Senior Expert Consultant'],
            ['id' => 9, 'name' => 'Specialist Consultant'],
            ['id' => 10, 'name' => 'Head Creative'],
            ['id' => 11, 'name' => 'Head Development'],
            ['id' => 12, 'name' => 'Front-End Developer'],
            ['id' => 13, 'name' => 'Back-End Developer'],
            ['id' => 14, 'name' => 'Web Developer'],
            ['id' => 15, 'name' => 'Fullstack Developer'],
            ['id' => 16, 'name' => 'Creative Management & Development'],
            ['id' => 17, 'name' => 'Economic & Business Analyst'],
        ]);

                // 3. BUAT AKUN SUPER ADMIN
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@kantor.com',
            'password' => Hash::make('password123'),
            'divisi_id' => 1,
            'posisi_id' => 1,
            'status_aktif' => 'aktif',
            'tanggal_masuk' => now(),
            'attendance_token' => (string) Str::uuid(),
            'company_name' => 'Damirich Group',
            'level_position' => 'Partner',
            'alamat_saat_ini' => 'Jl. Sudirman No. 1, Jakarta',
            'institutional_formal_education' => 'Harvard University - MBA',
            'formal_education' => 'S2/ Master Degree',
            'study_program' => 'Master Of business Adiministration',
            'start_date_formal_education' => '2020-08-01',
            'end_date_formal_education' => '2022-05-30',
            'non_formal_education' => 'Certified Accurate Profesional',
            'types_non_formal_education' => 'Certified Program',
            'program_name_non_formal' => 'Certified Professional Accountant',
            'institution_non_formal' => 'Ikatan Akuntan Indonesia',
            'working_experience' => 'Memiliki Pengalaman',
            'company_working_experience' => 'Global Consulting Group',
            'job_position_working_experience' => 'Chief Executive Officer',
            'job_responsibilities' => 'Mengelola strategi bisnis dan operasional perusahaan.',
            'social_media' => 'LinkedIn',
            'url_social_media' => 'https://linkedin.com/in/superadmin',
            'whatsapp' => '+6281234567890',
            // ✅ TAMBAHKAN INI
            'user_id_code' => 'DMRADMIN', // ID khusus untuk Super Admin
        ]);

        // 4. ✅ BUAT AKUN ADMIN BIASA (Manager)
        User::create([
            'name' => 'Admin Operasional',
            'email' => 'manager@kantor.com',
            'password' => Hash::make('password123'),
            'divisi_id' => 2,
            'posisi_id' => 4,
            'status_aktif' => 'aktif',
            'tanggal_masuk' => now(),
            'attendance_token' => (string) Str::uuid(),
            'company_name' => 'Damirich Group',
            'level_position' => 'Manager',
            'alamat_saat_ini' => 'Jl. Thamrin No. 10, Jakarta',
            'institutional_formal_education' => 'Universitas Indonesia - S1 Manajemen',
            'formal_education' => 'S1/ Bachelor Degree',
            'study_program' => 'MANAGEMENT',
            'start_date_formal_education' => '2018-09-01',
            'end_date_formal_education' => '2022-06-30',
            'non_formal_education' => 'Leadership Training Program',
            'types_non_formal_education' => 'Certificate Training',
            'program_name_non_formal' => 'Operational Leadership',
            'institution_non_formal' => 'Damirich Academy',
            'working_experience' => 'Memiliki Pengalaman',
            'company_working_experience' => 'PT. Solusi Bisnis Indonesia',
            'job_position_working_experience' => 'Operations Manager',
            'job_responsibilities' => 'Mengawasi operasional harian dan koordinasi tim.',
            'social_media' => 'LinkedIn',
            'url_social_media' => 'https://linkedin.com/in/managerops',
            'whatsapp' => '+6281298765432',
            // ✅ TAMBAHKAN INI
            'user_id_code' => 'DMRMNGR1',
        ]);

        // 5. BUAT AKUN KARYAWAN CONTOH
        User::create([
            'name' => 'Karyawan Contoh',
            'email' => 'user@kantor.com',
            'password' => Hash::make('password123'),
            'divisi_id' => 5,
            'posisi_id' => 15,
            'status_aktif' => 'aktif',
            'tanggal_masuk' => now(),
            'attendance_token' => (string) Str::uuid(),
            'company_name' => 'Damirich Group',
            'level_position' => 'Employee',
            'alamat_saat_ini' => 'Jl. Merdeka No. 123, Bandung',
            'institutional_formal_education' => 'Institut Teknologi Bandung - S1 Informatika',
            'formal_education' => 'S1/ Bachelor Degree',
            'study_program' => 'INFORMATICS ENGINEERING',
            'start_date_formal_education' => '2019-09-01',
            'end_date_formal_education' => '2023-06-30',
            'non_formal_education' => 'BREVET A DAN B',
            'types_non_formal_education' => 'Certificate Training',
            'program_name_non_formal' => 'Brevet Pajak A & B',
            'institution_non_formal' => 'BPPK Kemenkeu',
            'working_experience' => 'Memiliki Pengalaman',
            'company_working_experience' => 'Tech Startup XYZ',
            'job_position_working_experience' => 'Junior Developer',
            'job_responsibilities' => 'Mengembangkan fitur frontend dan backend aplikasi.',
            'social_media' => 'Instagram',
            'url_social_media' => 'https://instagram.com/karyawancontoh',
            'whatsapp' => '+6281287654321',
            // ✅ TAMBAHKAN INI
            'user_id_code' => 'DMRUSR01',
        ]);
    }
}