<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. The admin account is deliberately
     * NOT seeded here — it's created via `php artisan admin:create`
     * (no public signup UI, matches the original app's design).
     */
    public function run(): void
    {
        $this->call([
            BrandSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
