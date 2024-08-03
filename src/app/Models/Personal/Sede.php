<?php

namespace App\Models\Personal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sede extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'Nombre',
        'Direccion',
        'Telefono',
        'CodigoEmpresa',
        'CodigoDepartamento',
        'Vigente'
    ];
}
