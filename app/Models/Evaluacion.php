<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluacion extends Model
{
    protected $table = 'evaluaciones';
    protected $primaryKey = 'id_evaluacion';
    public $timestamps = false;
    protected $fillable = [
        'id_modulo', 'titulo', 'descripcion', 'tipo'
    ];

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class, 'id_modulo', 'id_modulo');
    }
    public function preguntas(): HasMany
    {
        return $this->hasMany(Pregunta::class, 'id_evaluacion', 'id_evaluacion');
    }
    public function resultados(): HasMany
    {
        return $this->hasMany(Resultado::class, 'id_evaluacion', 'id_evaluacion');
    }
}
