<?php
// Script de prueba para enviar un email usando la configuración de Laravel
use Illuminate\Support\Facades\Mail;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Create kernel and bootstrap
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    Mail::raw('Este es un correo de prueba desde tmp_send_test_mail.php', function($message){
        $message->to(config('mail.from.address'))
                ->subject('Prueba de envío - ' . date('Y-m-d H:i:s'));
    });
    echo "Mail sent attempt finished. Check logs or your inbox.\n";
} catch (Exception $e) {
    echo "Exception while sending mail: " . $e->getMessage() . "\n";
}
