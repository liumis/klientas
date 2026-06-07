<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $email = (string) env('INITIAL_ADMIN_EMAIL', 'liumis@liumis.com');

        if (User::query()->where('email', $email)->exists()) {
            return;
        }

        User::query()->create([
            'name' => (string) env('INITIAL_ADMIN_NAME', 'admin'),
            'email' => $email,
            'password' => Hash::make((string) env('INITIAL_ADMIN_PASSWORD', 'liudas')),
            'send_notifications' => true,
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $email = (string) env('INITIAL_ADMIN_EMAIL', 'liumis@liumis.com');

        User::query()->where('email', $email)->delete();
    }
};
