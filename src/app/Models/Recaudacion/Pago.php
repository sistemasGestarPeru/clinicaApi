<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{

    use HasFactory;
    public $timestamps = false;

    protected $table = 'pago';
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'CodigoTrabajador',
        'CodigoMedioPago',
        'CodigoCuentaBancaria',
        'CodigoCaja',
        'NumeroOperacion',
        'Fecha',
        'Monto',
        'Vigente',
        'Lote',
        'Referencia',
        'CodigoBilleteraDigital'
    ];
}
