<?php

namespace App\Models\Recaudacion\PagoTrabajadores;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagoPlanilla extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'pagopersonal';
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'Codigo',
        'CodigoSistemaPensiones',
        'CodigoTrabajador',
        'Mes',
        'MontoPension',
        'MontoSeguro',
        'MontoDescuento',
        'MontoBono',
        'ReciboHonorarios'
    ];
}
