<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

// ✅ NAMA CLASS SESUAI NAMA FILE
class RegisterController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'whatsapp' => 'required|string',
            'company_name' => 'required|string',
            'company_type' => 'required|string',
        ]);

        // Cegah registrasi email admin
        if ($request->email === 'admin@kantor.com') {
            return response()->json([
                'message' => 'Email ini khusus untuk Super Admin.'
            ], 403);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'whatsapp' => $request->whatsapp,
            'company_name' => $request->company_name,
            'company_type' => $request->company_type,
            'divisi_id' => null,
            'posisi_id' => null,
            'status_aktif' => 'aktif',
            'tanggal_masuk' => now(),
            'attendance_token' => (string) Str::uuid(),
            'level_position' => 'Employee', // 👈 USER BIASA
            // ... field lainnya sama seperti sebelumnya
        ]);

        return response()->json([
            'message' => 'Registrasi berhasil!',
            'user' => [
                ...$user->toArray(),
                'foto_profil_url' => null,
            ],
        ]);
    }

    // ✅ FUNGSI BARU UNTUK REGISTER ADMIN
    public function registerAdmin(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'whatsapp' => 'required|string',
            'company_name' => 'required|string',
            'company_type' => 'required|string',
        ]);

        // Cegah registrasi email super admin
        if ($request->email === 'admin@kantor.com') {
            return response()->json([
                'message' => 'Email ini khusus untuk Super Admin.'
            ], 403);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'whatsapp' => $request->whatsapp,
            'company_name' => $request->company_name,
            'company_type' => $request->company_type,
            'divisi_id' => null,
            'posisi_id' => null,
            'status_aktif' => 'aktif',
            'tanggal_masuk' => now(),
            'attendance_token' => (string) Str::uuid(),
            'level_position' => 'Manager', // 👈 INI YANG BEDA!
            // ... field lainnya sama
        ]);

        return response()->json([
            'message' => 'Registrasi admin berhasil!',
            'user' => [
                ...$user->toArray(),
                'foto_profil_url' => null,
            ],
        ]);
    }
}