<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            AsesorSeeder::class,
            TipoTramiteSeeder::class,
            DocumentoRequeridoSeeder::class,
            ConfiguracionSeeder::class,
            AvisoPrivacidadSeeder::class,
            SiteContentSeeder::class,
            PostSeeder::class,
            PropiedadSeeder::class,
            CrmConfigSeeder::class,
            TestimonioSeeder::class,
        ]);

        // Shield v4: generar permisos primero, luego asignar a roles
        $this->command->info('Generando permisos Shield v4...');
        Artisan::call('shield:generate', ['--all' => true, '--panel' => 'admin', '--no-interaction' => true]);

        $this->command->info('Asignando permisos al rol super_admin...');
        $this->call(SuperAdminRoleSeeder::class);

        $this->command->info('Asignando permisos al rol asesor...');
        $this->call(AsesorRoleSeeder::class);

        $this->command->info('Limpiando caché de permisos...');
        Artisan::call('permission:cache-reset');
        $this->command->info('Permisos listos.');

        $this->command->info('Creando rutas de prueba...');
        $this->call(RutaAsesorSeeder::class);
    }
}
