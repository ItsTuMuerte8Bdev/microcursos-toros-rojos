<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    // Tabla asociada
    protected $table = 'categorias';
    // Clave primaria
    protected $primaryKey = 'id_categoria';
    // Desactiva timestamps automáticos
    public $timestamps = false;
    // Campos asignables masivamente
    protected $fillable = [
        'nombre', 'descripcion'
    ];
    public function cursos(): HasMany
    {
        return $this->hasMany(Curso::class, 'id_categoria', 'id_categoria');
    }
}
