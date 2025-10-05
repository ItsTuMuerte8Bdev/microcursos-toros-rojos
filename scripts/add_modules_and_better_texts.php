<?php
// Script to improve module titles/descriptions and add an extra module (with lessons) for courses 4..9
$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    echo ".env not found at $envPath\n";
    exit(1);
}
$env = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$config = [];
foreach ($env as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    if (!strpos($line, '=')) continue;
    list($k,$v) = explode('=', $line, 2);
    $config[trim($k)] = trim($v);
}
$host = $config['DB_HOST'] ?? '127.0.0.1';
$port = $config['DB_PORT'] ?? '3306';
$db   = $config['DB_DATABASE'] ?? '';
$user = $config['DB_USERNAME'] ?? '';
$pass = $config['DB_PASSWORD'] ?? '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    echo "DB connection failed: " . $e->getMessage() . "\n";
    exit(2);
}

// We'll iterate courses 4..9
$courseIds = range(4,9);
$createdModules = 0;
$updatedModules = 0;
$createdLessons = 0;
$updatedLessons = 0;

foreach ($courseIds as $cid) {
    // Find existing modules for the course
    $stm = $pdo->prepare('SELECT id_modulo, titulo FROM modulos WHERE id_curso = ? ORDER BY orden ASC');
    $stm->execute([$cid]);
    $mods = $stm->fetchAll(PDO::FETCH_ASSOC);
    // Improve existing module(s) titles/descriptions
    $i = 1;
    foreach ($mods as $m) {
        $newTitle = 'Módulo ' . $i . ': Conceptos y práctica — ' . 'Parte ' . $i;
        $newDesc = 'Este módulo cubre los conceptos fundamentales y ejercicios prácticos para el curso. Incluye lecturas, ejemplos y pequeñas actividades para aplicar lo aprendido.';
        $upd = $pdo->prepare('UPDATE modulos SET titulo = ?, descripcion = ? WHERE id_modulo = ?');
        $upd->execute([$newTitle, $newDesc, $m['id_modulo']]);
        $updatedModules++;
        $i++;
    }
    // If course has only 1 module, add a second (y opcionalmente un tercero)
    $countMods = count($mods);
    if ($countMods < 2) {
        $ord = $countMods + 1;
        $ins = $pdo->prepare('INSERT INTO modulos (id_curso, titulo, descripcion, orden) VALUES (?, ?, ?, ?)');
        $title = 'Módulo ' . $ord . ': Aplicación práctica';
        $desc = 'Módulo orientado a la aplicación práctica y proyectos cortos para consolidar los conceptos vistos en el curso.';
        $ins->execute([$cid, $title, $desc, $ord]);
        $newModId = $pdo->lastInsertId();
        $createdModules++;

        // create two lessons for the new module
        $lessonA = [
            'id_modulo' => $newModId,
            'titulo' => 'Lección 1: Proyecto guiado — ' . $title,
            'contenido' => '<p>Proyecto guiado paso a paso para aplicar lo visto en el módulo.</p>',
            'tipo' => 'practica',
            'recurso_url' => null,
            'example_html' => '<div class="border p-2"><strong>Proyecto guiado</strong><p>Sigue los pasos y sube tu entrega.</p></div>',
            'activity_options' => json_encode([['text' => 'Completa el proyecto y sube un enlace o captura', 'safe' => false, 'description' => 'Entrega un enlace a tu demo o captura de pantalla.'] ], JSON_UNESCAPED_UNICODE),
            'quiz_options' => json_encode([['id'=>1,'text'=>'¿Completaste el proyecto?','correct'=>true]], JSON_UNESCAPED_UNICODE),
        ];
        $lessonB = [
            'id_modulo' => $newModId,
            'titulo' => 'Lección 2: Retos y mejoras — ' . $title,
            'contenido' => '<p>Pequeños retos para optimizar y mejorar tu proyecto.</p>',
            'tipo' => 'practica',
            'recurso_url' => null,
            'example_html' => '<div class="border p-2"><strong>Reto</strong><p>Implementa una mejora y documenta el cambio.</p></div>',
            'activity_options' => json_encode([['text' => 'Implementa una mejora y documenta el impacto', 'safe' => true, 'description' => 'Incluye antes/después o explicación.'] ], JSON_UNESCAPED_UNICODE),
            'quiz_options' => json_encode([['id'=>1,'text'=>'¿Qué mejora implementaste?','correct'=>true]], JSON_UNESCAPED_UNICODE),
        ];
        $insL = $pdo->prepare('INSERT INTO lecciones (id_modulo, titulo, contenido, tipo, recurso_url, example_html, activity_options, quiz_options) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $insL->execute([$lessonA['id_modulo'], $lessonA['titulo'], $lessonA['contenido'], $lessonA['tipo'], $lessonA['recurso_url'], $lessonA['example_html'], $lessonA['activity_options'], $lessonA['quiz_options']]);
        $createdLessons++;
        $insL->execute([$lessonB['id_modulo'], $lessonB['titulo'], $lessonB['contenido'], $lessonB['tipo'], $lessonB['recurso_url'], $lessonB['example_html'], $lessonB['activity_options'], $lessonB['quiz_options']]);
        $createdLessons++;
    }
    // If only 2 modules exist and user wanted 3, you can add a third - currently we ensure at least 2
}

echo "Modules updated: $updatedModules, Modules created: $createdModules\n";
echo "Lessons created: $createdLessons, Lessons updated: $updatedLessons\n";
