<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    // 1. LIHAT PROFIL SENDIRI — KIRIM SEMUA FIELD
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'message' => 'Profil User Login',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'whatsapp' => $user->whatsapp,
                'company_name' => $user->company_name,
                'divisi_id' => $user->divisi_id,
                'posisi_id' => $user->posisi_id,
                'level_position' => $user->level_position,
                'status_aktif' => $user->status_aktif,
                'tanggal_masuk' => $user->tanggal_masuk,
                'user_id_code' => $user->user_id_code,
                'attendance_token' => $user->attendance_token,
                'foto_profil_url' => $user->foto_profil ? asset('storage/' . $user->foto_profil) : null,

                // ✅ FIELD HR LENGKAP — DIJAMIN ADA
                'alamat_saat_ini' => $user->alamat_saat_ini ?? '',
                'institutional_formal_education' => $user->institutional_formal_education ?? '',
                'formal_education' => $user->formal_education ?? '',
                'study_program' => $user->study_program ?? '',
                'start_date_formal_education' => $user->start_date_formal_education ?? '',
                'end_date_formal_education' => $user->end_date_formal_education ?? '',
                'non_formal_education' => $user->non_formal_education ?? '',
                'types_non_formal_education' => $user->types_non_formal_education ?? '',
                'program_name_non_formal' => $user->program_name_non_formal ?? '',
                'institution_non_formal' => $user->institution_non_formal ?? '',
                'working_experience' => $user->working_experience ?? '',
                'company_working_experience' => $user->company_working_experience ?? '',
                'job_position_working_experience' => $user->job_position_working_experience ?? '',
                'job_responsibilities' => $user->job_responsibilities ?? '',
                'social_media' => $user->social_media ?? '',
                'url_social_media' => $user->url_social_media ?? '',
                'history_mutasi' => $user->history_mutasi ?? '',

                // ✅ DOKUMEN FILE
                'ktp_file' => $user->ktp_file ?? '',
                'npwp_file' => $user->npwp_file ?? '',
                'bpjs_file' => $user->bpjs_file ?? '',
                'kontrak_kerja_file' => $user->kontrak_kerja_file ?? '',
                'ktp_file_url' => $user->ktp_file ? asset('storage/' . $user->ktp_file) : null,
                'npwp_file_url' => $user->npwp_file ? asset('storage/' . $user->npwp_file) : null,
                'bpjs_file_url' => $user->bpjs_file ? asset('storage/' . $user->bpjs_file) : null,
                'kontrak_kerja_file_url' => $user->kontrak_kerja_file ? asset('storage/' . $user->kontrak_kerja_file) : null,
            ],
        ]);
    }

    // 2. UPDATE DATA DIRI (Nama, Email, Foto)
    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'foto'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        if ($request->hasFile('foto')) {
            if ($user->foto_profil) {
                Storage::disk('public')->delete($user->foto_profil);
            }
            $path = $request->file('foto')->store('profil', 'public');
            $user->foto_profil = $path;
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'whatsapp' => $user->whatsapp,
                'company_name' => $user->company_name,
                'divisi_id' => $user->divisi_id,
                'posisi_id' => $user->posisi_id,
                'level_position' => $user->level_position,
                'status_aktif' => $user->status_aktif,
                'tanggal_masuk' => $user->tanggal_masuk,
                'user_id_code' => $user->user_id_code,
                'attendance_token' => $user->attendance_token,
                'foto_profil_url' => $user->foto_profil ? asset('storage/' . $user->foto_profil) : null,

                // ✅ SEMUA FIELD HR DIKIRIM ULANG
                'alamat_saat_ini' => $user->alamat_saat_ini ?? '',
                'institutional_formal_education' => $user->institutional_formal_education ?? '',
                'formal_education' => $user->formal_education ?? '',
                'study_program' => $user->study_program ?? '',
                'start_date_formal_education' => $user->start_date_formal_education ?? '',
                'end_date_formal_education' => $user->end_date_formal_education ?? '',
                'non_formal_education' => $user->non_formal_education ?? '',
                'types_non_formal_education' => $user->types_non_formal_education ?? '',
                'program_name_non_formal' => $user->program_name_non_formal ?? '',
                'institution_non_formal' => $user->institution_non_formal ?? '',
                'working_experience' => $user->working_experience ?? '',
                'company_working_experience' => $user->company_working_experience ?? '',
                'job_position_working_experience' => $user->job_position_working_experience ?? '',
                'job_responsibilities' => $user->job_responsibilities ?? '',
                'social_media' => $user->social_media ?? '',
                'url_social_media' => $user->url_social_media ?? '',
                'history_mutasi' => $user->history_mutasi ?? '',
                'ktp_file' => $user->ktp_file ?? '',
                'npwp_file' => $user->npwp_file ?? '',
                'bpjs_file' => $user->bpjs_file ?? '',
                'kontrak_kerja_file' => $user->kontrak_kerja_file ?? '',
                'ktp_file_url' => $user->ktp_file ? asset('storage/' . $user->ktp_file) : null,
                'npwp_file_url' => $user->npwp_file ? asset('storage/' . $user->npwp_file) : null,
                'bpjs_file_url' => $user->bpjs_file ? asset('storage/' . $user->bpjs_file) : null,
                'kontrak_kerja_file_url' => $user->kontrak_kerja_file ? asset('storage/' . $user->kontrak_kerja_file) : null,
            ],
        ]);
    }

    // 3. GANTI PASSWORD
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Password lama salah!'], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password berhasil diganti!']);
    }

    // ✅ 4. UPDATE DATA PRIBADI — UNTUK KARYAWAN
    public function updatePersonalInfo(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'whatsapp' => 'nullable|string',
            'alamat_saat_ini' => 'nullable|string',
            'institutional_formal_education' => 'nullable|string',
            'formal_education' => 'nullable|string',
            'study_program' => 'nullable|string',
            'start_date_formal_education' => 'nullable|date',
            'end_date_formal_education' => 'nullable|date',
            'non_formal_education' => 'nullable|string',
            'types_non_formal_education' => 'nullable|string',
            'program_name_non_formal' => 'nullable|string',
            'institution_non_formal' => 'nullable|string',
            'working_experience' => 'nullable|string',
            'company_working_experience' => 'nullable|string',
            'job_position_working_experience' => 'nullable|string',
            'job_responsibilities' => 'nullable|string',
            'social_media' => 'nullable|string',
            'url_social_media' => 'nullable|url',
        ]);

        $user->update($request->only([
            'name', 'email', 'whatsapp', 'alamat_saat_ini',
            'institutional_formal_education', 'formal_education', 'study_program',
            'start_date_formal_education', 'end_date_formal_education',
            'non_formal_education', 'types_non_formal_education', 'program_name_non_formal',
            'institution_non_formal', 'working_experience', 'company_working_experience',
            'job_position_working_experience', 'job_responsibilities',
            'social_media', 'url_social_media'
        ]));

        return response()->json([
            'message' => 'Profil pribadi berhasil diperbarui.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'whatsapp' => $user->whatsapp,
                'company_name' => $user->company_name,
                'divisi_id' => $user->divisi_id,
                'posisi_id' => $user->posisi_id,
                'level_position' => $user->level_position,
                'status_aktif' => $user->status_aktif,
                'tanggal_masuk' => $user->tanggal_masuk,
                'user_id_code' => $user->user_id_code,
                'attendance_token' => $user->attendance_token,
                'foto_profil_url' => $user->foto_profil ? asset('storage/' . $user->foto_profil) : null,

                // ✅ SEMUA FIELD HR DIKIRIM
                'alamat_saat_ini' => $user->alamat_saat_ini ?? '',
                'institutional_formal_education' => $user->institutional_formal_education ?? '',
                'formal_education' => $user->formal_education ?? '',
                'study_program' => $user->study_program ?? '',
                'start_date_formal_education' => $user->start_date_formal_education ?? '',
                'end_date_formal_education' => $user->end_date_formal_education ?? '',
                'non_formal_education' => $user->non_formal_education ?? '',
                'types_non_formal_education' => $user->types_non_formal_education ?? '',
                'program_name_non_formal' => $user->program_name_non_formal ?? '',
                'institution_non_formal' => $user->institution_non_formal ?? '',
                'working_experience' => $user->working_experience ?? '',
                'company_working_experience' => $user->company_working_experience ?? '',
                'job_position_working_experience' => $user->job_position_working_experience ?? '',
                'job_responsibilities' => $user->job_responsibilities ?? '',
                'social_media' => $user->social_media ?? '',
                'url_social_media' => $user->url_social_media ?? '',
                'history_mutasi' => $user->history_mutasi ?? '',
                'ktp_file' => $user->ktp_file ?? '',
                'npwp_file' => $user->npwp_file ?? '',
                'bpjs_file' => $user->bpjs_file ?? '',
                'kontrak_kerja_file' => $user->kontrak_kerja_file ?? '',
                'ktp_file_url' => $user->ktp_file ? asset('storage/' . $user->ktp_file) : null,
                'npwp_file_url' => $user->npwp_file ? asset('storage/' . $user->npwp_file) : null,
                'bpjs_file_url' => $user->bpjs_file ? asset('storage/' . $user->bpjs_file) : null,
                'kontrak_kerja_file_url' => $user->kontrak_kerja_file ? asset('storage/' . $user->kontrak_kerja_file) : null,
            ],
        ]);
    }

    // ✅ 5. UPDATE DATA HR LENGKAP — HANYA UNTUK ADMIN
    public function updateProfile(Request $request)
    {
        $request->validate([
            'divisi_id' => 'required|exists:divisi,id',
            'posisi_id' => 'required|exists:posisi,id',
            'status_aktif' => 'required|in:aktif,nonaktif',
            'history_mutasi' => 'nullable|string',
            'ktp_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'npwp_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'bpjs_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'kontrak_kerja_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $user = auth()->user();

        $saveFile = function ($file, $oldPath = null) {
            if (!$file) return $oldPath;
            if ($oldPath) Storage::disk('public')->delete($oldPath);
            return $file->store('uploads', 'public');
        };

        $user->update([
            'divisi_id' => $request->divisi_id,
            'posisi_id' => $request->posisi_id,
            'status_aktif' => $request->status_aktif,
            'history_mutasi' => $request->history_mutasi,
            'ktp_file' => $saveFile($request->file('ktp_file'), $user->ktp_file),
            'npwp_file' => $saveFile($request->file('npwp_file'), $user->npwp_file),
            'bpjs_file' => $saveFile($request->file('bpjs_file'), $user->bpjs_file),
            'kontrak_kerja_file' => $saveFile($request->file('kontrak_kerja_file'), $user->kontrak_kerja_file),
            
            // Field HR Baru
            'alamat_saat_ini' => $request->alamat_saat_ini ?? '',
            'institutional_formal_education' => $request->institutional_formal_education ?? '',
            'level_position' => $request->level_position ?? '',
            'formal_education' => $request->formal_education ?? '',
            'study_program' => $request->study_program ?? '',
            'start_date_formal_education' => $request->start_date_formal_education ?? '',
            'end_date_formal_education' => $request->end_date_formal_education ?? '',
            'non_formal_education' => $request->non_formal_education ?? '',
            'types_non_formal_education' => $request->types_non_formal_education ?? '',
            'program_name_non_formal' => $request->program_name_non_formal ?? '',
            'institution_non_formal' => $request->institution_non_formal ?? '',
            'working_experience' => $request->working_experience ?? '',
            'company_working_experience' => $request->company_working_experience ?? '',
            'job_position_working_experience' => $request->job_position_working_experience ?? '',
            'job_responsibilities' => $request->job_responsibilities ?? '',
            'social_media' => $request->social_media ?? '',
            'url_social_media' => $request->url_social_media ?? '',
        ]);

        return response()->json([
            'message' => 'Data karyawan berhasil diperbarui.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'whatsapp' => $user->whatsapp,
                'company_name' => $user->company_name,
                'divisi_id' => $user->divisi_id,
                'posisi_id' => $user->posisi_id,
                'level_position' => $user->level_position,
                'status_aktif' => $user->status_aktif,
                'tanggal_masuk' => $user->tanggal_masuk,
                'user_id_code' => $user->user_id_code,
                'attendance_token' => $user->attendance_token,
                'foto_profil_url' => $user->foto_profil ? asset('storage/' . $user->foto_profil) : null,

                // ✅ SEMUA FIELD HR DIKIRIM
                'alamat_saat_ini' => $user->alamat_s ?? '',
                'institutional_formal_education' => $user->institutional_formal_education ?? '',
                'formal_education' => $user->formal_education ?? '',
                'study_program' => $user->study_program ?? '',
                'start_date_formal_education' => $user->start_date_formal_education ?? '',
                'end_date_formal_education' => $user->end_date_formal_education ?? '',
                'non_formal_education' => $user->non_formal_education ?? '',
                'types_non_formal_education' => $user->types_non_formal_education ?? '',
                'program_name_non_formal' => $user->program_name_non_formal ?? '',
                'institution_non_formal' => $user->institution_non_formal ?? '',
                'working_experience' => $user->working_experience ?? '',
                'company_working_experience' => $user->company_working_experience ?? '',
                'job_position_working_experience' => $user->job_position_working_experience ?? '',
                'job_responsibilities' => $user->job_responsibilities ?? '',
                'social_media' => $user->social_media ?? '',
                'url_social_media' => $user->url_social_media ?? '',
                'history_mutasi' => $user->history_mutasi ?? '',
                'ktp_file' => $user->ktp_file ?? '',
                'npwp_file' => $user->npwp_file ?? '',
                'bpjs_file' => $user->bpjs_file ?? '',
                'kontrak_kerja_file' => $user->kontrak_kerja_file ?? '',
                'ktp_file_url' => $user->ktp_file ? asset('storage/' . $user->ktp_file) : null,
                'npwp_file_url' => $user->npwp_file ? asset('storage/' . $user->npwp_file) : null,
                'bpjs_file_url' => $user->bpjs_file ? asset('storage/' . $user->bpjs_file) : null,
                'kontrak_kerja_file_url' => $user->kontrak_kerja_file ? asset('storage/' . $user->kontrak_kerja_file) : null,
            ],
        ]);
    }

    // ✅ 6. UNGGAH FOTO PROFIL SAJA
    public function uploadFotoProfil(Request $request)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $user = $request->user();

        if ($user->foto_profil) {
            Storage::disk('public')->delete($user->foto_profil);
        }

        $path = $request->file('foto')->store('profil', 'public');
        $user->foto_profil = $path;
        $user->save();

        return response()->json([
            'message' => 'Foto profil berhasil diunggah',
            'foto_profil_url' => asset('storage/' . $path),
        ]);
    }
}