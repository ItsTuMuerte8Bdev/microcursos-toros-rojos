<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('lecciones')->select('id_leccion','titulo','activity_options')->whereIn('id_leccion',[4,5])->get();
echo json_encode($rows, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . PHP_EOL;
