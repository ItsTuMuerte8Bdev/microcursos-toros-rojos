<?php
// Export microcursos data to a single JSON file suitable for rebuilding seeders.
// Outputs storage/app/microcursos_export_{timestamp}.json

$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) { echo ".env not found\n"; exit(1); }
$env = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$config = [];
foreach ($env as $line) {
    $line = trim($line);
    if ($line === '' || strpos($line, '#') === 0) continue;
    if (strpos($line, '=') === false) continue;
    list($k, $v) = explode('=', $line, 2);
    $config[trim($k)] = trim($v);
}
if (empty($config['DB_HOST'] ?? '') || empty($config['DB_DATABASE'] ?? '')) { echo "DB config incomplete in .env\n"; exit(1); }
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['DB_HOST'], $config['DB_PORT'] ?? '3306', $config['DB_DATABASE']);
try { $pdo = new PDO($dsn, $config['DB_USERNAME'] ?? null, $config['DB_PASSWORD'] ?? null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]); }
catch (Exception $e) { echo "DB error: " . $e->getMessage() . "\n"; exit(2); }

$db = $config['DB_DATABASE'];
// Candidate tables to include (order matters for foreign keys readability)
$candidate = ['categorias','cursos','modulos','lecciones','evaluaciones','preguntas','respuestas','resultados','progresos','recursos','actividad_enlaces','actividades','enlaces','users'];
// discover which of these exist in the current DB
$placeholders = implode(',', array_fill(0, count($candidate), '?'));
$st = $pdo->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_name IN ($placeholders)");
$params = array_merge([$db], $candidate);
$st->execute($params);
$found = array_column($st->fetchAll(PDO::FETCH_ASSOC),'table_name');

$export = ['meta'=>['database'=>$db,'exported_at'=>date('c')],'tables'=>[]];

foreach ($found as $table) {
    try {
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        $export['tables'][$table] = $rows;
    } catch (Exception $e) {
        $export['tables'][$table] = ['_error' => $e->getMessage()];
    }
}

// Build nested structure for microcursos (if relevant tables exist)
$nested = ['cursos'=>[]];
if (in_array('cursos',$found)) {
    $courses = $pdo->query('SELECT * FROM cursos ORDER BY id_curso')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($courses as $c) {
        $course = $c;
        $cid = $c['id_curso'] ?? $c['id'];
        // modules
        $mods = [];
        if (in_array('modulos',$found)) {
            $pst = $pdo->prepare('SELECT * FROM modulos WHERE id_curso = ? ORDER BY id_modulo');
            $pst->execute([$cid]); $modsRows = $pst->fetchAll(PDO::FETCH_ASSOC);
            foreach ($modsRows as $m) {
                $mod = $m;
                $mid = $m['id_modulo'] ?? $m['id'];
                // lessons
                $lessons = [];
                if (in_array('lecciones',$found)) {
                    $lst = $pdo->prepare('SELECT * FROM lecciones WHERE id_modulo = ? ORDER BY id_leccion');
                    $lst->execute([$mid]); $lrows = $lst->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($lrows as $L) {
                        $lesson = $L;
                        // optionally include evaluation and its questions
                        if (!empty($L['id_modulo']) && in_array('evaluaciones',$found)) {
                            // find evaluation for module
                            $est = $pdo->prepare('SELECT * FROM evaluaciones WHERE id_modulo = ?');
                            $est->execute([$mid]); $eval = $est->fetchAll(PDO::FETCH_ASSOC);
                            if ($eval) {
                                $lesson['evaluaciones'] = $eval;
                                // for each evaluation attach preguntas+respuestas
                                foreach ($eval as $e) {
                                    if (in_array('preguntas',$found)) {
                                        $qst = $pdo->prepare('SELECT * FROM preguntas WHERE id_evaluacion = ? ORDER BY id_pregunta');
                                        $qst->execute([$e['id_evaluacion'] ?? $e['id']]); $qrows = $qst->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($qrows as &$q) {
                                            if (in_array('respuestas',$found)) {
                                                $rst = $pdo->prepare('SELECT * FROM respuestas WHERE id_pregunta = ? ORDER BY id_respuesta');
                                                $rst->execute([$q['id_pregunta'] ?? $q['id']]); $q['respuestas'] = $rst->fetchAll(PDO::FETCH_ASSOC);
                                            }
                                        }
                                        $lesson['preguntas'] = $qrows;
                                    }
                                }
                            }
                        }
                        $lessons[] = $lesson;
                    }
                }
                $mod['lecciones'] = $lessons;
                $mods[] = $mod;
            }
        }
        $course['modulos'] = $mods;
        $nested['cursos'][] = $course;
    }
}

$export['nested'] = $nested;

$dir = __DIR__ . '/../storage/app';
if (!is_dir($dir)) mkdir($dir, 0777, true);
$fn = $dir . '/microcursos_export_' . date('Ymd_His') . '.json';
file_put_contents($fn, json_encode($export, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

// print summary
echo "Exported tables: \n";
foreach ($export['tables'] as $t => $rows) {
    if (isset($rows[0]) && is_array($rows[0])) $count = count($rows); else if (is_array($rows) && isset($rows['_error'])) $count = 'ERROR'; else $count = count($rows);
    echo " - $t: $count rows\n";
}
$nc = count($nested['cursos']);
echo "Nested cursos: $nc\n";
echo "File written: $fn\n";
echo "Done.\n";
