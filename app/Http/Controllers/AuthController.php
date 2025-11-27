<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // === FUNCTION REGISTRASI (SUDAH DIPERBAIKI) ===
    public function register(Request $request)
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            // Validasi tambahan
            'divisi_id' => 'required',
            'posisi_id' => 'required',
            'tanggal_masuk' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // 2. Simpan SEMUA data ke tabel Users (Sesuai yang kita perbaiki tadi)
            $user = User::create([
                'name' => $request->nama,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                // Data tambahan ini sekarang masuk ke tabel users
                'divisi_id' => $request->divisi_id,
                'posisi_id' => $request->posisi_id,
                'tanggal_masuk' => $request->tanggal_masuk,
            ]);

            // 3. Generate Token agar user langsung login
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Registrasi berhasil!',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ], 201);

        } catch (\Exception $e) {
            // Jika masih error, tampilkan pesan aslinya biar gampang didiagnosa
            return response()->json([
                'message' => 'Terjadi kesalahan server.',
                'error' => $e->getMessage()
            ], 500);
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