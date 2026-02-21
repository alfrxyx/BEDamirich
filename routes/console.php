<?php

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('user:generate-id', function () {
    $users = User::whereNull('user_id_code')->get();

    if ($users->isEmpty()) {
        $this->info('✅ Semua user sudah punya ID.');
        return;
    }

    foreach ($users as $user) {
        do {
            $code = 'DMR' . strtoupper(Str::random(5));
        } while (User::where('user_id_code', $code)->exists());

        $user->user_id_code = $code;
        $user->save();
        $this->info("✅ {$user->name} → {$code}");
    }

    $this->info("✅ Selesai! Total: {$users->count()} user diperbarui.");
});