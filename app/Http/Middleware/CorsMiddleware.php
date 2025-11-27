<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // PENTING: Whitelist URL Front-End Anda
        $allowedOrigins = ['http://localhost:5173', 'http://127.0.0.1:5173'];
        $origin = $request->headers->get('Origin');
        
        if (in_array($origin, $allowedOrigins)) {
            $headers = [
                'Access-Control-Allow-Origin' => $origin,
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, X-Auth-Token, Origin, Authorization',
                'Access-Control-Allow-Credentials' => 'true',
            ];
        } else {
            $headers = []; 
        }

        // Handle preflight request (OPTIONS)
        if ($request->isMethod('OPTIONS')) {
            return response()->json('ok', 200, $headers);
        }

        $response = $next($request);
        
        // Tambahkan header ke response
        foreach ($headers as $key => $value) {
            // Menggunakan header 'set' untuk menimpa jika sudah ada
            $response->headers->set($key, $value);
        }

        return $response;
    }
}