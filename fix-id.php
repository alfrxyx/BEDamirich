<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\User;
use Illuminate\Support\Str;

$users = User::whereNull('user_id_code')->get();

if ($users->isEmpty()) {
    echo "✅ Semua user sudah punya ID.\n";
    exit;
}

foreach ($users as $user) {
    do {
        $code = 'DMR' . strtoupper(Str::random(5));
    } while (User::where('user_id_code', $code)->exists());

    $user->user_id_code = $code;
    $user->save();
    echo "✅ {$user->name} → {$code}\n";
}

echo "✅ Selesai! Total: {$users->count()} user diperbarui.\n";