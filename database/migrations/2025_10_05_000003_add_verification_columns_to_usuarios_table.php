<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            // token usado para verificar el correo
            $table->string('verification_token', 128)->nullable()->after('estado');
            // fecha/hora en que se envió el token (para expirar enlaces)
            $table->timestamp('verification_sent_at')->nullable()->after('verification_token');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn(['verification_token', 'verification_sent_at']);
        });
    }
};
