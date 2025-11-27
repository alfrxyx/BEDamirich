<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    public function generateQr()
    {
        // 1. Buat Token Acak (Kunci Rahasia)
        $token = Str::random(10); // Contoh: "aX9s7d8K2j"

        // 2. Simpan Token ini di Cache server selama 30 detik
        // Token ini akan hangus otomatis setelah 30 detik
        Cache::put('qr_absensi_token', $token, 30);

        // 3. Generate Gambar QR Code (Format SVG)
        // Isi QR-nya adalah token tadi
        $qrImage = QrCode::size(200)->generate($token);

        return response()->json([
            'status' => 'success',
            'token_saat_ini' => $token, // Ini ditampilkan buat kita ngetes di Postman
            'qr_image' => (string) $qrImage // Ini nanti dirender di Frontend Layar Kantor
        ]);
    }
}