<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\IsAdmin; 
use App\Http\Middleware\CorsMiddleware; 

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        
        // --- 1. Bypass CSRF untuk Route Tertentu (INI SOLUSINYA) ---
        // Kita izinkan route 'register' dan 'karyawan' diakses tanpa token CSRF
        $middleware->validateCsrfTokens(except: [
            'register',      // <--- Penting: ini route tempat kamu submit form
            'karyawan',      // <--- Jaga-jaga jika submit ke /karyawan
            'karyawans',     // <--- Bentuk pluralnya (sesuai nama tabel/route biasanya)
            'api/*',         // <--- Semua route API aman
            'sanctum/csrf-cookie' // <--- Untuk login nanti
        ]);

        // --- 2. Middleware Global (CORS) ---
        $middleware->append(CorsMiddleware::class); 

        // --- 3. Middleware Group API (Sanctum) ---
        $middleware->api(prepend: [
             \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // --- 4. Pendaftaran Alias ---
        $middleware->alias([
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class, 
            'is_admin' => \App\Http\Middleware\IsAdmin::class, 
        ]);
        
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();