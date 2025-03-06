<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocalDocumentoVenta extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'localdocumentoventa';
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'CodigoSede',
        'CodigoTipoDocumentoVenta',
        'Serie',
        'TipoProducto',
        'Vigente',
        'CodigoSerieDocumentoVenta'
    ];
}
