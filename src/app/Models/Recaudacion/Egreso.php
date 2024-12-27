<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Egreso extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'egreso';
    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'Fecha',
        'Monto',
        'CodigoCaja',
        'CodigoTrabajador',
        'CodigoMedioPago',
        'CodigoCuentaOrigen'
    ];
}
