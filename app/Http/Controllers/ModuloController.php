<?php
namespace App\Http\Controllers;

use App\Models\Modulo;
use App\Models\Curso;
use Illuminate\Http\Request;

class ModuloController extends Controller
{
    public function index($cursoId)
    {
        // Retorna los módulos de un curso específico con lecciones y evaluaciones
        $curso = Curso::findOrFail($cursoId);
        return $curso->modulos()->with(['lecciones', 'evaluaciones'])->get();
    }
}
