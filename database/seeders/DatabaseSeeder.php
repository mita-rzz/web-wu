<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->error('Seeder ini hanya untuk pengembangan (password akun contoh lemah).');

            return;
        }

        $this->call([
            UserSeeder::class,
            TaxonomySeeder::class,
            WriteupSeeder::class,
        ]);
    }
}