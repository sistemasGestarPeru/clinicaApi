<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagoDocumentoVenta extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'pagodocumentoventa';
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'CodigoPago',
        'CodigoDocumentoVenta',
        'Monto',
        'Vigente'
    ];
}
