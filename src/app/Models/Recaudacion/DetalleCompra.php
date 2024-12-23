<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleCompra extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'detallecompra';

    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'Descripcion',
        'MontoTotal',
        'MontoIGV',
        'Cantidad',
        'TipoGravado',
        'CodigoProducto',
        'CodigoCompra'
    ];

}
