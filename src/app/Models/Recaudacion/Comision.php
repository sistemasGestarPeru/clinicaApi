<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comision extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'comision';
    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'Codigo',
        'CodigoDocumentoVenta',
        'CodigoContrato',
        'CodigoPagoComision',
        'Monto',
        'TipoDocumento',
        'Serie',
        'Numero',
        'Vigente',
        'CodigoMedico',
        'Comentario',
        'CodigoTrabajador',
        'FechaCreacion',
        'CodigoComisionReferencia',
        'CodigoSede'
    ];
}
