<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            ConfiguracionSeeder::class,
            SiteContentSeeder::class,
            PostSeeder::class,
            PropiedadSeeder::class,
            CrmConfigSeeder::class,
            TestimonioSeeder::class,
        ]);
    }
}
