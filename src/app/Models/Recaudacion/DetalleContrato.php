<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleContrato extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'detallecontrato';

    protected $fillable = [
        'MontoTotal',
        'Cantidad',
        'Descripcion',
        'CodigoContrato',
        'CodigoProducto',
        'Descuento'
    ];
}
