<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Respuesta extends Model
{
    protected $table = 'respuestas';
    protected $primaryKey = 'id_respuesta';
    public $timestamps = false;
    protected $fillable = [
        'id_pregunta', 'texto', 'es_correcta'
    ];

    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class, 'id_pregunta', 'id_pregunta');
    }
}
