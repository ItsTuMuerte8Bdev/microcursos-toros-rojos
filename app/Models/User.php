<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Mapear al esquema existente (tabla en español)
    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';
    public $incrementing = true;
    protected $keyType = 'int';

    /**
     * Atributos asignables
     */
    protected $fillable = [
        'nombre',
        'apellido',
        'correo',
        'password',
        'rol',
    ];

    /**
     * Atributos ocultos
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Accesor corto para compatibilidad si el código espera 'name' o 'email'
    public function getNameAttribute()
    {
        return trim(($this->nombre ?? '') . ' ' . ($this->apellido ?? ''));
    }

    public function getEmailAttribute()
    {
        return $this->correo ?? null;
    }

    /**
     * Provide an "id" attribute that maps to the real primary key (id_usuario).
     * Some parts of the app expect $user->id; since this model uses a custom
     * primary key name we expose it via an accessor so existing code continues
     * to work without changing route/controller logic.
     */
    public function getIdAttribute()
    {
        return $this->{$this->getKeyName()};
    }
}
