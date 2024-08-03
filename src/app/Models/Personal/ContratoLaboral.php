<?php

namespace App\Models\Personal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContratoLaboral extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'FechaInicio',
        'FechaFin',
        'Tipo',
        'Tiempo',
        'CodigoEmpresa',
        'CodigoTrabajador',
        'Vigente'
    ];
}
