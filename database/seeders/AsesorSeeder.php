<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AsesorSeeder extends Seeder
{
    public function run(): void
    {
        // Crear rol asesor si no existe
        $rolAsesor = Role::firstOrCreate(
            ['name' => 'asesor', 'guard_name' => 'web']
        );

        $asesores = [
            ['name' => 'Carlos Mendoza López',     'email' => 'carlos.mendoza@consultoria.mx'],
            ['name' => 'María Fernanda Ríos',       'email' => 'maria.rios@consultoria.mx'],
            ['name' => 'Jorge Alberto Castillo',    'email' => 'jorge.castillo@consultoria.mx'],
            ['name' => 'Lucía Hernández Torres',    'email' => 'lucia.hernandez@consultoria.mx'],
            ['name' => 'Roberto Vázquez Sánchez',   'email' => 'roberto.vazquez@consultoria.mx'],
            ['name' => 'Ana Patricia Morales',      'email' => 'ana.morales@consultoria.mx'],
            ['name' => 'Miguel Ángel Reyes',        'email' => 'miguel.reyes@consultoria.mx'],
            ['name' => 'Claudia Beatriz Gutiérrez', 'email' => 'claudia.gutierrez@consultoria.mx'],
            ['name' => 'Fernando Espinoza Cruz',    'email' => 'fernando.espinoza@consultoria.mx'],
            ['name' => 'Daniela Romero Peña',       'email' => 'daniela.romero@consultoria.mx'],
        ];

        foreach ($asesores as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'     => $data['name'],
                    'password' => Hash::make('password'),
                    'activo'   => true,
                ]
            );

            if (! $user->hasRole('asesor')) {
                $user->assignRole($rolAsesor);
            }
        }

        $this->command->info('✓ 10 asesores creados con rol "asesor" y contraseña "password".');
    }
}
