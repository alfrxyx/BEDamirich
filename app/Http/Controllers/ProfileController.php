<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    // 1. LIHAT PROFIL SENDIRI
    public function show(Request $request)
    {
        return response()->json([
            'message' => 'Profil User Login',
            'data' => $request->user()
        ]);
    }

    // 2. UPDATE DATA DIRI (Nama, Email, Foto)
    public function update(Request $request)
    {
        $user = $request->user(); // Ambil user yg sedang login

        $request->validate([
            'nama'  => 'required|string|max:255',
            'email' => 'required|email|unique:karyawans,email,' . $user->id, // Abaikan email milik sendiri
            'foto'  => 'nullable|image|max:2048'
        ]);

        // Handle Foto
        if ($request->hasFile('foto')) {
            // Hapus foto lama jika ada (biar gak numpuk)
            if ($user->foto_profil) {
                Storage::disk('public')->delete($user->foto_profil);
            }
            // Simpan foto baru
            $path = $request->file('foto')->store('profil', 'public');
            $user->foto_profil = $path;
        }

        $user->nama = $request->nama;
        $user->email = $request->email;
        $user->save(); // Simpan perubahan

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'data' => $user
        ]);
    }

    // 3. GANTI PASSWORD
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:8|confirmed', // confirmed artinya harus ada field new_password_confirmation
        ]);

        $user = $request->user();

        // Cek apakah password lama benar?
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Password lama salah!'], 400);
        }

        // Update Password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password berhasil diganti!']);
    }
}