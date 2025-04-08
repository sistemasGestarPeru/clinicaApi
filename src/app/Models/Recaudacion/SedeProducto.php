<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SedeProducto extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'sedeproducto';
    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'CodigoProducto',
        'CodigoSede',
        'Negociable',
        'Precio',
        'PrecioMinimo',
        'Stock',
        'Vigente',
        'CodigoTipoGravado',
        'Controlado',
        'StockMinimo'
    ];
}
