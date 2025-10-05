<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MicrocursosExportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This seeder reads the latest storage/app/microcursos_export_*.json file
     * and inserts its tables into the database. It will disable foreign key
     * checks, truncate the target tables and insert rows preserving ids.
     */
    public function run()
    {
        $dir = storage_path('app');
        if (!is_dir($dir)) {
            $this->command->error('storage/app directory not found. Run `php artisan storage:link` or create the folder.');
            return;
        }

        $files = glob($dir . DIRECTORY_SEPARATOR . 'microcursos_export_*.json');
        if (empty($files)) {
            $this->command->error('No export file found in storage/app matching microcursos_export_*.json');
            return;
        }

        // pick the latest file by modified time
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        $file = $files[0];
        $this->command->info("Using export file: $file");

        $raw = file_get_contents($file);
        $data = json_decode($raw, true);
        if ($data === null) {
            $this->command->error('Failed to decode JSON from export file');
            return;
        }

        $tables = $data['tables'] ?? [];

        // Order matters for FK constraints. We'll be resilient to differences between the
        // exported table names and the current DB (singular/plural, users/usuarios, progreso/progresos)
    // Default import order. By default we skip importing existing progress rows (progreso)
    // because importing progreso from an export may mark lessons as completed for all users.
    // To allow importing progreso explicitly, set IMPORT_PROGRESO=true in your .env.
    $order = ['categorias','cursos','modulos','lecciones','evaluaciones','preguntas','respuestas','resultados','progreso','usuarios'];
    $importProgreso = env('IMPORT_PROGRESO', false);

        // helper to find a key in the exported tables with fallbacks
        $findExportKey = function(array $tablesKeys, string $preferred) {
            // exact match
            if (in_array($preferred, $tablesKeys, true)) return $preferred;
            // singular/plural swap
            if (substr($preferred, -1) === 's') {
                $sing = substr($preferred, 0, -1);
                if (in_array($sing, $tablesKeys, true)) return $sing;
            } else {
                $plural = $preferred . 's';
                if (in_array($plural, $tablesKeys, true)) return $plural;
            }
            // english/spanish common mappings
            $map = [
                'users' => 'usuarios',
                'usuario' => 'usuarios',
                'usuarios' => 'usuarios',
                'progresos' => 'progreso',
                'progreso' => 'progreso',
                'result' => 'resultados',
            ];
            if (isset($map[$preferred]) && in_array($map[$preferred], $tablesKeys, true)) return $map[$preferred];
            // try inverse map: maybe export has 'users' but preferred is 'usuarios'
            foreach ($map as $k => $v) {
                if ($preferred === $v && in_array($k, $tablesKeys, true)) return $k;
            }
            // no candidate found
            return null;
        };

        // Do not wrap in a DB transaction because TRUNCATE in MySQL causes implicit commits.
        // We'll adapt behavior depending on the database driver:
        // - MySQL: disable FOREIGN_KEY_CHECKS, use truncate()
        // - PostgreSQL: use TRUNCATE ... RESTART IDENTITY CASCADE and reset sequences after inserts
        try {
            $driver = DB::getDriverName();
            $exportKeys = array_keys($tables);

            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
            } elseif ($driver === 'pgsql') {
                // disable triggers which effectively disables FK checks for the session
                DB::statement("SET session_replication_role = 'replica'");
            }
            foreach ($order as $table) {
                $exportKey = $findExportKey($exportKeys, $table);
                if ($exportKey === null) {
                    $this->command->info("Skipping missing table: $table");
                    continue;
                }
                // safety: skip progreso unless explicitly enabled in .env
                if (strtolower($table) === 'progreso' && !$importProgreso) {
                    $this->command->info("Skipping table 'progreso' because IMPORT_PROGRESO is not true. Set IMPORT_PROGRESO=true in .env to import it.");
                    continue;
                }
                $rows = $tables[$exportKey];
                if (isset($rows['_error'])) {
                    $this->command->error("Table $table contained an error in export: " . $rows['_error']);
                    continue;
                }
                $count = count($rows);
                $this->command->info("Seeding table $table (from export key: $exportKey): $count rows");

                if ($count === 0) {
                    // still truncate to reset state
                    DB::table($table)->truncate();
                    continue;
                }

                // truncate before inserting into the destination table name
                try {
                    if ($driver === 'pgsql') {
                        // In Postgres, use CASCADE to handle FK dependencies and restart identities
                        DB::statement("TRUNCATE TABLE \"{$table}\" RESTART IDENTITY CASCADE");
                    } else {
                        // default/traditional behavior (MySQL, SQLite)
                        DB::table($table)->truncate();
                    }
                } catch (\Exception $e) {
                    $this->command->error("Failed to truncate destination table $table: " . $e->getMessage());
                    continue;
                }

                // insert in chunks to avoid large single queries
                $chunks = array_chunk($rows, 500);
                foreach ($chunks as $chunk) {
                    // ensure keys are strings (DB expects associative arrays)
                    $normalized = array_map(function ($r) {
                        // cast nested arrays/objects to JSON strings where needed
                        foreach ($r as $k => $v) {
                            if (is_array($v)) {
                                $r[$k] = json_encode($v, JSON_UNESCAPED_UNICODE);
                            }
                        }
                        return $r;
                    }, $chunk);
                    try {
                        DB::table($table)->insert($normalized);
                    } catch (\Exception $e) {
                        $this->command->error("Failed inserting into $table: " . $e->getMessage());
                    }
                }
                // After inserting into Postgres, ensure any SERIAL/SEQUENCE is set to the max(id)
                if ($driver === 'pgsql' && isset($rows) && count($rows) > 0) {
                    // try to detect a primary key column name convention (id_*). Use the first matching key.
                    $firstRow = $rows[0];
                    $pk = null;
                    foreach (array_keys($firstRow) as $k) {
                        if (str_starts_with($k, 'id_') || $k === 'id') {
                            $pk = $k;
                            break;
                        }
                    }
                    if ($pk) {
                        try {
                            // set sequence to max(pk) to avoid nextval conflicts
                            DB::statement("SELECT setval(pg_get_serial_sequence('\"{$table}\"', '{$pk}'), COALESCE((SELECT MAX(\"{$pk}\") FROM \"{$table}\"), 1))");
                        } catch (\Exception $e) {
                            // best-effort: log but don't fail the whole import
                            $this->command->error("Failed to reset sequence for {$table}.{$pk}: " . $e->getMessage());
                        }
                    }
                }
            }
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            } elseif ($driver === 'pgsql') {
                DB::statement("SET session_replication_role = 'origin'");
            }
            $this->command->info('Seeding from export completed successfully.');
        } catch (\Exception $e) {
            $this->command->error('Seeding failed: ' . $e->getMessage());
        } finally {
            // best-effort to restore FK checks if an unexpected issue happened
            try {
                if (isset($driver) && $driver === 'mysql') {
                    DB::statement('SET FOREIGN_KEY_CHECKS=1');
                } elseif (isset($driver) && $driver === 'pgsql') {
                    DB::statement("SET session_replication_role = 'origin'");
                }
            } catch (\Exception $_) {
                // ignore
            }
        }
    }
}
