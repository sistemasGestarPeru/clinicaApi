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

    // Mutadores para convertir los valores en mayúsculas
    public function setNombresAttribute($value)
    {
        $this->attributes['Nombres'] = strtoupper($value);
    }

    public function setApellidosAttribute($value)
    {
        $this->attributes['Apellidos'] = strtoupper($value);
    }

    public function setDireccionAttribute($value)
    {
        $this->attributes['Direccion'] = strtoupper($value);
    }
}
