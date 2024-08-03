<?php

namespace App\Models\Personal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'Codigo';
    public function trabajador()
    {
        return $this->hasOne(Trabajador::class, 'Codigo', 'Codigo');
    }
    //protected $table = 'persona';
    protected $fillable = [
        // 'Codigo',
        'Nombres',
        'Apellidos',
        'Direccion',
        'Celular',
        'Correo',
        'NumeroDocumento',
        'CodigoTipoDocumento',
        'CodigoNacionalidad',
        'CodigoDepartamento',
        'Vigente'
    ];
}
