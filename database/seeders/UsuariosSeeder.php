<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuariosSeeder extends Seeder
{
    public function run()
    {
        // Use environment variables for seeded passwords so re-seeding doesn't unintentionally
        // overwrite production/admin credentials. Fallback values are provided for local dev.
        $adminPassword = env('SEED_ADMIN_PASSWORD', 'admin123');
        $instructorPassword = env('SEED_INSTRUCTOR_PASSWORD', 'instructor123');
        $empleadoPassword = env('SEED_EMPLEADO_PASSWORD', 'empleado123');

        $users = [
            [
                'nombre' => 'Admin',
                'apellido' => 'Principal',
                'correo' => 'admin@demo.com',
                'password' => Hash::make($adminPassword),
                'rol' => 'admin',
                'proveedor_oauth' => null,
                'proveedor_id' => null,
                'sexo' => 'no binario',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Juan',
                'apellido' => 'Instructor',
                'correo' => 'juan@demo.com',
                'password' => Hash::make($instructorPassword),
                'rol' => 'instructor',
                'proveedor_oauth' => null,
                'proveedor_id' => null,
                'sexo' => 'masculino',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Ana',
                'apellido' => 'Empleado',
                'correo' => 'ana@demo.com',
                'password' => Hash::make($empleadoPassword),
                'rol' => 'empleado',
                'proveedor_oauth' => null,
                'proveedor_id' => null,
                'sexo' => 'femenino',
                'estado' => 'activo',
            ],
        ];

        foreach ($users as $u) {
            $match = ['correo' => $u['correo']];
            // avoid overwriting provider fields if null in export: explicitly set the columns we want to update/insert
            $values = $u;
            DB::table('usuarios')->updateOrInsert($match, $values);
        }
    }
}
