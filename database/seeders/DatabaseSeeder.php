<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            AsesorSeeder::class,
            ConfiguracionSeeder::class,
            SiteContentSeeder::class,
            PostSeeder::class,
            PropiedadSeeder::class,
            CrmConfigSeeder::class,
            TestimonioSeeder::class,
        ]);

        if (class_exists(\Silber\Bouncer\Bouncer::class)) {
            $this->command->info('Regenerando permisos de Shield...');
            \Artisan::call('shield:generate', ['--all' => true, '--panel' => 'admin', '--no-interaction' => true]);
            $this->command->info('Permisos de Shield regenerados.');
        }
    }
}
