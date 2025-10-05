<?php
// Script to assign a recommended recurso_url to existing lecciones based on course title keywords
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

// Map keyword => recommended resource URL (Spanish, reliable channels/articles)
$map = [
    'JavaScript' => 'https://www.youtube.com/watch?v=upDLs1sn7g4', // freeCodeCamp JS en español (tutorial largo)
    'Git' => 'https://www.youtube.com/watch?v=USjZcfj8yxE', // Curso Git y GitHub - freeCodeCamp (ES)
    'Diseño' => 'https://www.uxd-club.com/introduccion-a-la-accesibilidad-web/', // artículo de referencia
    'UX' => 'https://www.youtube.com/watch?v=8K8k2sQ1_O8', // charla/curso introductorio en español
    'Finanzas' => 'https://www.youtube.com/watch?v=3x4rQG3oYBs', // video explicativo en español (ejemplo)
    'proyectos' => 'https://www.youtube.com/watch?v=G7bC5YqQG0A', // video sobre metodologías ágiles en español
    'Hojas' => 'https://www.youtube.com/watch?v=4v2f2KZrV9M', // ejemplo: hojas de calculo en español
    'Documentos' => 'https://www.youtube.com/watch?v=2Vwq3gq3iHM',
    'Contraseñas' => 'https://www.youtube.com/watch?v=GZ6LwYqjNQ0',
    'Ciberseguridad' => 'https://www.youtube.com/watch?v=HqW4p7kTpeI',
    'Comunicación' => 'https://www.youtube.com/watch?v=7G7dV1w_kZk',
];

// Fetch all lessons and their module->course titles
$sql = 'SELECT l.id_leccion, l.titulo as leccion_titulo, m.id_modulo, c.titulo as curso_titulo FROM lecciones l JOIN modulos m ON m.id_modulo = l.id_modulo JOIN cursos c ON c.id_curso = m.id_curso';
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$updated = 0;
foreach ($rows as $r) {
    $cursoTitulo = $r['curso_titulo'];
    $assigned = null;
    foreach ($map as $kw => $url) {
        if (stripos($cursoTitulo, $kw) !== false || stripos($r['leccion_titulo'], $kw) !== false) {
            $assigned = $url;
            break;
        }
    }
    if (!$assigned) {
        // fallback to a generic Spanish learning playlist
        $assigned = 'https://www.youtube.com/watch?v=Qk0zUZW-U_M';
    }
    // update only if recurso_url is empty or null
    $check = $pdo->prepare('SELECT recurso_url FROM lecciones WHERE id_leccion = ?');
    $check->execute([$r['id_leccion']]);
    $cur = $check->fetch(PDO::FETCH_ASSOC);
    if (empty($cur['recurso_url'])) {
        $upd = $pdo->prepare('UPDATE lecciones SET recurso_url = ? WHERE id_leccion = ?');
        $upd->execute([$assigned, $r['id_leccion']]);
        echo "Assigned recurso to id_leccion={$r['id_leccion']}: $assigned\n";
        $updated++;
    }
}

echo "Done. Updated $updated lessons.\n";
