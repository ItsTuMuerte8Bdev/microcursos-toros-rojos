<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // usuarios (spanish users table used by app)
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id('id_usuario');
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('correo', 150)->unique();
            $table->string('password', 255);
            $table->enum('rol', ['admin', 'instructor', 'empleado'])->default('empleado');
            $table->string('proveedor_oauth', 50)->nullable();
            $table->string('proveedor_id', 150)->nullable();
            $table->enum('sexo', ['masculino', 'femenino', 'no binario'])->default('no binario');
            $table->timestamp('fecha_registro')->useCurrent();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            // optional fields added by later migrations
            $table->string('remember_token', 100)->nullable();
            $table->timestamp('email_verified_at')->nullable();
        });

        Schema::create('categorias', function (Blueprint $table) {
            $table->id('id_categoria');
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
        });

        Schema::create('cursos', function (Blueprint $table) {
            $table->id('id_curso');
            $table->string('titulo', 200);
            $table->text('descripcion')->nullable();
            $table->unsignedBigInteger('id_categoria')->nullable();
            $table->enum('nivel', ['basico', 'intermedio', 'avanzado'])->default('basico');
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->foreign('id_categoria')->references('id_categoria')->on('categorias')->onDelete('set null');
            $table->foreign('creado_por')->references('id_usuario')->on('usuarios')->onDelete('set null');
        });

        Schema::create('modulos', function (Blueprint $table) {
            $table->id('id_modulo');
            $table->unsignedBigInteger('id_curso');
            $table->string('titulo', 200);
            $table->text('descripcion')->nullable();
            $table->integer('orden')->default(0);
            $table->foreign('id_curso')->references('id_curso')->on('cursos')->onDelete('cascade');
        });

        Schema::create('lecciones', function (Blueprint $table) {
            $table->id('id_leccion');
            $table->unsignedBigInteger('id_modulo');
            $table->string('titulo', 200);
            $table->text('contenido')->nullable();
            $table->enum('tipo', ['teoria', 'video', 'practica'])->default('teoria');
            $table->string('recurso_url', 255)->nullable();
            // extra fields
            $table->text('example_html')->nullable();
            $table->json('activity_options')->nullable();
            $table->json('quiz_options')->nullable();
            $table->foreign('id_modulo')->references('id_modulo')->on('modulos')->onDelete('cascade');
        });

        Schema::create('evaluaciones', function (Blueprint $table) {
            $table->id('id_evaluacion');
            $table->unsignedBigInteger('id_modulo');
            $table->string('titulo', 200);
            $table->text('descripcion')->nullable();
            $table->enum('tipo', ['quiz', 'practica', 'examen_final'])->default('quiz');
            $table->foreign('id_modulo')->references('id_modulo')->on('modulos')->onDelete('cascade');
        });

        Schema::create('preguntas', function (Blueprint $table) {
            $table->id('id_pregunta');
            $table->unsignedBigInteger('id_evaluacion');
            $table->text('enunciado');
            $table->enum('tipo', ['opcion_multiple', 'verdadero_falso', 'abierta'])->default('opcion_multiple');
            $table->integer('puntaje')->default(1);
            $table->foreign('id_evaluacion')->references('id_evaluacion')->on('evaluaciones')->onDelete('cascade');
        });

        Schema::create('respuestas', function (Blueprint $table) {
            $table->id('id_respuesta');
            $table->unsignedBigInteger('id_pregunta');
            $table->string('texto', 255);
            $table->boolean('es_correcta')->default(false);
            $table->foreign('id_pregunta')->references('id_pregunta')->on('preguntas')->onDelete('cascade');
        });

        Schema::create('progreso', function (Blueprint $table) {
            $table->id('id_progreso');
            $table->unsignedBigInteger('id_usuario');
            $table->unsignedBigInteger('id_leccion');
            $table->boolean('completado')->default(false);
            $table->timestamp('fecha_completado')->nullable();
            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios')->onDelete('cascade');
            $table->foreign('id_leccion')->references('id_leccion')->on('lecciones')->onDelete('cascade');
        });

        Schema::create('resultados', function (Blueprint $table) {
            $table->id('id_resultado');
            $table->unsignedBigInteger('id_usuario');
            $table->unsignedBigInteger('id_evaluacion');
            $table->integer('puntaje_obtenido');
            $table->timestamp('fecha')->useCurrent();
            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios')->onDelete('cascade');
            $table->foreign('id_evaluacion')->references('id_evaluacion')->on('evaluaciones')->onDelete('cascade');
        });

        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id('id_notificacion');
            $table->unsignedBigInteger('id_usuario');
            $table->text('mensaje');
            $table->boolean('leido')->default(false);
            $table->timestamp('fecha_envio')->useCurrent();
            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // drop in reverse order to avoid FK issues
        Schema::dropIfExists('notificaciones');
        Schema::dropIfExists('resultados');
        Schema::dropIfExists('progreso');
        Schema::dropIfExists('respuestas');
        Schema::dropIfExists('preguntas');
        Schema::dropIfExists('evaluaciones');
        Schema::dropIfExists('lecciones');
        Schema::dropIfExists('modulos');
        Schema::dropIfExists('cursos');
        Schema::dropIfExists('categorias');
        Schema::dropIfExists('usuarios');
    }
};
