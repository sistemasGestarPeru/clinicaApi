<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'compra';

    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'CodigoTipoDocumentoVenta',
        'CodigoSede',
        'Serie',
        'Numero',
        'Fecha',
        'FormaPago',
        'CodigoProveedor',
        'CodigoTrabajador',
        'Vigente',
        'CodigoDocumentoReferencia',
        'Tipo'
    ];
}
