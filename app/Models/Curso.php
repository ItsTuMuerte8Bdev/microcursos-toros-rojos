<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curso extends Model
{
    protected $table = 'cursos';
    protected $primaryKey = 'id_curso';
    public $timestamps = false;
    protected $fillable = [
        'titulo', 'descripcion', 'id_categoria', 'nivel', 'creado_por', 'fecha_creacion', 'estado'
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_categoria', 'id_categoria');
    }
    public function creador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'creado_por', 'id_usuario');
    }
    public function modulos(): HasMany
    {
        return $this->hasMany(Modulo::class, 'id_curso', 'id_curso');
    }
}
