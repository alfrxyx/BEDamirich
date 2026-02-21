<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        if ($request->email === 'admin@kantor.com') {
            return response()->json([
                'message' => 'Email ini khusus untuk Admin dan tidak bisa didaftarkan manual.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'whatsapp' => 'required|string',
            'company_name' => 'nullable|string',
            'company_type' => 'required|string|in:KONSULTAN BISNIS,FOOD N BEVERAGE,CONSTRUCTION,OUTSORCE,JASA,OTHER',
            'tanggal_masuk' => 'required|date',
        ], [
            'company_type.in' => 'Jenis company tidak valid.',
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'company_type.required' => 'Jenis company wajib dipilih.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        try {
            $userIdCode = null;
            $maxAttempts = 10;
            for ($i = 0; $i < $maxAttempts; $i++) {
                $code = 'DMR' . strtoupper(Str::random(5));
                if (!User::where('user_id_code', $code)->exists()) {
                    $userIdCode = $code;
                    break;
                }
            }

            if (!$userIdCode) {
                \Log::error('Gagal generate user_id_code unik setelah 10 percobaan');
                return response()->json(['message' => 'Terjadi kesalahan internal. Silakan coba lagi.'], 500);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'whatsapp' => $request->whatsapp,
                'company_name' => $request->company_name ?? 'Damirich Group',
                'company_type' => $request->company_type,
                'divisi_id' => null,
                'posisi_id' => null,
                'level_position' => 'Employee',
                'tanggal_masuk' => $request->tanggal_masuk,
                'user_id_code' => $userIdCode,
                'attendance_token' => Str::uuid(),
                'status_aktif' => 'aktif',
                'alamat_saat_ini' => '',
                'institutional_formal_education' => '',
                'formal_education' => '',
                'study_program' => '',
                'start_date_formal_education' => null,
                'end_date_formal_education' => null,
                'non_formal_education' => '',
                'types_non_formal_education' => '',
                'program_name_non_formal' => '',
                'institution_non_formal' => '',
                'working_experience' => '',
                'company_working_experience' => '',
                'job_position_working_experience' => '',
                'job_responsibilities' => '',
                'social_media' => '',
                'url_social_media' => '',
                'history_mutasi' => '',
                'ktp_file' => '',
                'npwp_file' => '',
                'bpjs_file' => '',
                'kontrak_kerja_file' => '',
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Registrasi berhasil!',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ], 201);

        } catch (QueryException $e) {
            if ($e->getCode() === '23000' && strpos($e->getMessage(), 'Duplicate entry') !== false) {
                \Log::error('Duplicate user_id_code pada registrasi', [
                    'email' => $request->email,
                    'message' => $e->getMessage()
                ]);
                return response()->json(['message' => 'Terjadi konflik ID. Silakan coba lagi.'], 500);
            }
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Registrasi Gagal: ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan server saat registrasi.'], 500);
        }
    }

    public function registerAdmin(Request $request)
    {
        if ($request->email === 'admin@kantor.com') {
            return response()->json([
                'message' => 'Email ini khusus untuk Super Admin.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'whatsapp' => 'required|string',
            'company_name' => 'nullable|string',
            'company_type' => 'required|string|in:KONSULTAN BISNIS,FOOD N BEVERAGE,CONSTRUCTION,OUTSORCE,JASA,OTHER',
            'tanggal_masuk' => 'required|date',
        ], [
            'company_type.in' => 'Jenis company tidak valid.',
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'company_type.required' => 'Jenis company wajib dipilih.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        try {
            $userIdCode = null;
            $maxAttempts = 10;
            for ($i = 0; $i < $maxAttempts; $i++) {
                $code = 'DMR' . strtoupper(Str::random(5));
                if (!User::where('user_id_code', $code)->exists()) {
                    $userIdCode = $code;
                    break;
                }
            }

            if (!$userIdCode) {
                \Log::error('Gagal generate user_id_code unik untuk admin');
                return response()->json(['message' => 'Terjadi kesalahan internal.'], 500);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'whatsapp' => $request->whatsapp,
                'company_name' => $request->company_name ?? 'Damirich Group',
                'company_type' => $request->company_type,
                'divisi_id' => null,
                'posisi_id' => null,
                'level_position' => 'Manager',
                'tanggal_masuk' => $request->tanggal_masuk,
                'user_id_code' => $userIdCode,
                'attendance_token' => Str::uuid(),
                'status_aktif' => 'aktif',
                'alamat_saat_ini' => '',
                'institutional_formal_education' => '',
                'formal_education' => '',
                'study_program' => '',
                'start_date_formal_education' => null,
                'end_date_formal_education' => null,
                'non_formal_education' => '',
                'types_non_formal_education' => '',
                'program_name_non_formal' => '',
                'institution_non_formal' => '',
                'working_experience' => '',
                'company_working_experience' => '',
                'job_position_working_experience' => '',
                'job_responsibilities' => '',
                'social_media' => '',
                'url_social_media' => '',
                'history_mutasi' => '',
                'ktp_file' => '',
                'npwp_file' => '',
                'bpjs_file' => '',
                'kontrak_kerja_file' => '',
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Registrasi admin berhasil!',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ], 201);

        } catch (QueryException $e) {
            if ($e->getCode() === '23000' && strpos($e->getMessage(), 'Duplicate entry') !== false) {
                \Log::error('Duplicate user_id_code pada registrasi admin', [
                    'email' => $request->email,
                    'message' => $e->getMessage()
                ]);
                return response()->json(['message' => 'Terjadi konflik ID. Silakan coba lagi.'], 500);
            }
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Registrasi Admin Gagal: ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan server saat registrasi admin.'], 500);
        }
    }

    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Login gagal. Cek email dan password Anda.'
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function logout()
    {
        Auth::user()->tokens()->delete();
        return response()->json(['message' => 'Logout berhasil']);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8',
            'confirm_password' => 'required|string|same:new_password',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Password lama salah.'], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password berhasil diperbarui!']);
    }
}