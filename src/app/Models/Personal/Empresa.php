<?php

namespace App\Models\Personal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'empresas';
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'Nombre',
        'RazonSocial',
        'RUC',
        'Direccion',
        'Correo',
        'Vigente',
        'PorcentajeDetraccion'
    ];


     // Mutadores para convertir los valores en mayúsculas
     public function setNombresAttribute($value)
     {
         $this->attributes['Nombre'] = strtoupper($value);
     }
 
     public function setRazonSocialAttribute($value)
     {
         $this->attributes['RazonSocial'] = strtoupper($value);
     }
 
     public function setDireccionAttribute($value)
     {
         $this->attributes['Direccion'] = strtoupper($value);
     }
}
