<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Progreso extends Model
{
    protected $table = 'progreso';
    protected $primaryKey = 'id_progreso';
    public $timestamps = false;
    protected $fillable = [
        'id_usuario', 'id_leccion', 'completado', 'fecha_completado'
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
    public function leccion(): BelongsTo
    {
        return $this->belongsTo(Leccion::class, 'id_leccion', 'id_leccion');
    }
}
