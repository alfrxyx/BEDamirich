<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

// ... (namespace dan use statement tetap sama)

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // 1. CEGAH REGISTRASI EMAIL ADMIN
        if ($request->email === 'admin@kantor.com') {
            return response()->json([
                'message' => 'Email ini khusus untuk Admin dan tidak bisa didaftarkan manual.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'divisi_id' => 'required',
            'tanggal_masuk' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        try {
            // 2. PAKSA SEMUA PENDAFTAR JADI KARYAWAN BIASA (ID 2)
            // Jadi meskipun dia pilih "Admin" di dropdown (kalau ada),
            // sistem akan tetap memaksa dia jadi "Staff".
            $posisiStaff = 2; 

            $user = User::create([
                'name' => $request->nama,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'divisi_id' => $request->divisi_id,
                'posisi_id' => $posisiStaff, // <--- KUNCI PENGAMAN
                'tanggal_masuk' => $request->tanggal_masuk,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Registrasi berhasil!',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan server.', 'error' => $e->getMessage()], 500);
        }
    }

    // === FUNCTION LOGIN ===
    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Login gagal. Cek email dan password Anda.'
            ], 401);
        }

        $user = User::where('email', $request['email'])->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function logout()
    {
        Auth::user()->tokens()->delete();
        return response()->json(['message' => 'Logout berhasil']);
    }
}