<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class Usuario extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;

    protected $fillable = [
        'nombre', 'apellido', 'correo', 'password', 'rol', 'proveedor_oauth', 'proveedor_id', 'fecha_registro', 'estado', 'sexo', 'verification_token', 'verification_sent_at', 'email_verified_at'
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Ensure any assigned password is hashed.
     * Accepts already-hashed values (bcrypt/argon2) and leaves them as-is.
     */
    public function setPasswordAttribute($value)
    {
        if (is_null($value)) {
            $this->attributes['password'] = null;
            return;
        }

        // If value already looks like a bcrypt/hash (starts with known prefix), keep it
        if (is_string($value) && (str_starts_with($value, '$2y$') || str_starts_with($value, '$2b$') || str_starts_with($value, '$argon2'))) {
            $this->attributes['password'] = $value;
            return;
        }

        // Otherwise hash the plain text password
        $this->attributes['password'] = Hash::make($value);
    }

    // If your column is 'correo' instead of 'email', tell Laravel which attribute is the username
    public function getAuthIdentifierName()
    {
        return 'id_usuario';
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Provide an "id" attribute that maps to the real primary key (id_usuario)
     * so code using $user->id works when the authenticated model is Usuario.
     */
    public function getIdAttribute()
    {
        return $this->{$this->getKeyName()};
    }

    // Convenience accessors for compatibility with views using ->name and ->email
    public function getNameAttribute()
    {
        return trim((($this->nombre ?? '') . ' ' . ($this->apellido ?? '')));
    }

    public function getEmailAttribute()
    {
        return $this->correo ?? null;
    }

    public function cursosCreados(): HasMany
    {
        return $this->hasMany(Curso::class, 'creado_por', 'id_usuario');
    }
    public function progreso(): HasMany
    {
        return $this->hasMany(Progreso::class, 'id_usuario', 'id_usuario');
    }
    public function resultados(): HasMany
    {
        return $this->hasMany(Resultado::class, 'id_usuario', 'id_usuario');
    }
    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'id_usuario', 'id_usuario');
    }
}
