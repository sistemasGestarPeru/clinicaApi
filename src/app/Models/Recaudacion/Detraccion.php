<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Detraccion extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'Detraccion';
    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'Monto',
        'CodigoDocumentoVenta',
        'CodigoCuentaBancaria',
        'CodigoPagoDetraccion'
    ];
}
