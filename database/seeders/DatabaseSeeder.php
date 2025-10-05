<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Centralized seeding: import the export JSON and optionally ensure a known admin user
        $this->call([
            MicrocursosExportSeeder::class,
            // optionally recreate a reproducible local admin/instructor/empleado
            UsuariosSeeder::class,
        ]);
    }
}
