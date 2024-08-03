<?php

namespace App\Models\Personal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsignacionSede extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'CodigoSede',
        'FechaInicio',
        'FechaFin',
        'CodigoTrabajador',
        'Vigente'
    ];
}
