<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'DocumentoVenta';
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
        'CodigoClienteEmpresa',
        'TotalGravado',
        'TotalExonerado',
        'TotalInafecto',
        'IGVTotal',
        'MontoTotal',
        'MontoPagado',
        'Estado',
        'EstadoFactura',
        'CodigoContratoProducto',
        'CodigoCaja',
        'Vigente'

    ];
}