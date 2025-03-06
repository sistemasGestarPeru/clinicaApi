<?php

namespace App\Models\Seguridad;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsuarioPerfil extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'Codigo';
    protected $table = 'usuario_perfil';

    protected $fillable = [
        'CodigoPersona',
        'CodigoAplicacion',
        'Vigente'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'CodigoPersona', 'CodigoPersona');
    }
}
