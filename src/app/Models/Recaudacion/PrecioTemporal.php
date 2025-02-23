<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrecioTemporal extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'precioTemporal';

    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'CodigoSede',
        'CodigoProducto',
        'Precio',
        'Cantidad',
        'Stock',
        'Vigente'
    ];
}
