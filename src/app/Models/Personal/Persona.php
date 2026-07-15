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

    public function scopeCboBuscarPersonas($query, $texto)
    {
        return $query->where(function($q) use ($texto) {
            $q->where('Nombres', 'LIKE', "%$texto%")
            ->orWhere('Apellidos', 'LIKE', "%$texto%")
            ->orWhereRaw("CONCAT(Apellidos, ' ', Nombres) LIKE ?", ["%{$texto}%"])
            ->where('Vigente', 1);
        });
    }
}
