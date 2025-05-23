<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Personal\Persona;
use App\Models\Seguridad\UsuarioPerfil;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'CodigoPersona', 'Codigo');
    }

    public function usuarioPerfiles()
    {
        return $this->hasMany(UsuarioPerfil::class, 'CodigoPersona', 'CodigoPersona');
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'CodigoPersona',
        'Vigente'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
