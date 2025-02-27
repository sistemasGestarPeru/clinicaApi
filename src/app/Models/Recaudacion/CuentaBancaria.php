<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuentaBancaria extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'cuentabancaria';
    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'CodigoEntidadBancaria',
        'CodigoEmpresa',
        'Numero',
        'CCI',
        'CodigoTipoMoneda',
        'Vigente',
        'Detraccion'
    ];
}
