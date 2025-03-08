<?php

namespace App\Models\Almacen\GuiaIngreso;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuiaIngreso extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'guiaingreso';
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'TipoDocumento',
        'Serie',
        'Numero',
        'Fecha',
        'Motivo',
        'CodigoTrabajador',
        'CodigoSede',
        'CodigoCompra',
        'Vigente'
    ];
}
