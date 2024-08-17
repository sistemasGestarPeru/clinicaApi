<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'Venta';
    protected $primaryKey = 'Codigo';

    protected $fillable = [

        'CodigoDocumentoReferencia',
        'CodigoTipoDocumentoVenta',
        'CodigoSede',
        'Serie',
        'Numero',
        'Fecha',
        'CodigoTrabajador',
        'CodigoPersona',
        'CodigoEmpresa',
        'TotalGravado',
        'TotalExonerado',
        'TotalInafecto',
        'IGVTotal',
        'MontoTotal',
        'MontoPagado',
        'Estado',
        'EstadoFacturacion',
        'CodigoContrato',
        'CodigoCaja',
        'Vigente'

    ];
}
