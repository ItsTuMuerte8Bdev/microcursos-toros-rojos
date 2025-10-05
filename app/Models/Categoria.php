<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $table = 'categorias';
    protected $primaryKey = 'id_categoria';
    public $timestamps = false;
    protected $fillable = [
        'nombre', 'descripcion'
    ];

    public function cursos(): HasMany
    {
        return $this->hasMany(Curso::class, 'id_categoria', 'id_categoria');
    }
}
