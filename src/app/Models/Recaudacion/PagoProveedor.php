<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagoProveedor extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'pagoproveedor';
    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'Codigo',
        'CodigoCuota',
        'CodigoProveedor',
        'TipoMoneda',
        'MontoMonedaExtranjera'
    ];
}
