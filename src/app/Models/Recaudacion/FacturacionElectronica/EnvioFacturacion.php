<?php

namespace App\Models\Recaudacion\FacturacionElectronica;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnvioFacturacion extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'enviofacturacion';
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'Tipo',
        'JSON',
        'URL',
        'Fecha',
        'CodigoTrabajador',
        'Estado',
        'CodigoDocumentoVenta',
        'CodigoAnulacion'
    ];
}
