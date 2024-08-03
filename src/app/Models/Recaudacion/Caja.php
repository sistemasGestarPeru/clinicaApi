<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'caja';
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'Codigo',
        'FechaInicio',
        'FechaFin',
        'Estado',
        'CodigoSede',
        'CodigoTrabajador'
    ];
}
