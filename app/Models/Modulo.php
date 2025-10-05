<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modulo extends Model
{
    protected $table = 'modulos';
    protected $primaryKey = 'id_modulo';
    public $timestamps = false;
    protected $fillable = [
        'id_curso', 'titulo', 'descripcion', 'orden'
    ];

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'id_curso', 'id_curso');
    }
    public function lecciones(): HasMany
    {
        return $this->hasMany(Leccion::class, 'id_modulo', 'id_modulo');
    }
    public function evaluaciones(): HasMany
    {
        return $this->hasMany(Evaluacion::class, 'id_modulo', 'id_modulo');
    }
}
