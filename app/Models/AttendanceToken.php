<?php

namespace App\Models; // PENTING: Harus ada namespace!

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'expires_at',
        'is_active',
    ];

    // Opsional: Cast expires_at sebagai Carbon instance
    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}