<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'servicio';
    protected $primaryKey = 'Codigo';
    
    protected $fillable = [
        'Codigo',
        'CodigoMotivoPago',
        'Descripcion',
        'TipoDocumento',
        'Serie',
        'Numero',
        'FechaDocumento',
        'IGV',
        'CodigoProveedor',
        'Monto',
        'CodigoSede'
    ];
}
