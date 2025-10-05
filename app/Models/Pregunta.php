<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pregunta extends Model
{
    protected $table = 'preguntas';
    protected $primaryKey = 'id_pregunta';
    public $timestamps = false;
    protected $fillable = [
        'id_evaluacion', 'enunciado', 'tipo', 'puntaje'
    ];

    public function evaluacion(): BelongsTo
    {
        return $this->belongsTo(Evaluacion::class, 'id_evaluacion', 'id_evaluacion');
    }
    public function respuestas(): HasMany
    {
        return $this->hasMany(Respuesta::class, 'id_pregunta', 'id_pregunta');
    }
}
