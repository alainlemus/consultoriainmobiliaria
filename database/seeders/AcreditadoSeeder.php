<?php

namespace Database\Seeders;

use App\Models\Acreditado;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AcreditadoSeeder extends Seeder
{
    public function run(): void
    {
        Acreditado::create([
            'name' => 'Test Acreditado',
            'email' => 'test.acreditado@consultoriainmobiliaria.com',
            'password' => Hash::make('ReviewPass2026!'),
            'activo' => true,
            'email_verified_at' => now(),
        ]);
    }
}
