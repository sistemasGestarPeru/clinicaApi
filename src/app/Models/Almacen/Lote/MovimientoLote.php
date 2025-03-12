<?php

namespace App\Models\Almacen\Lote;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoLote extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'movimientolote';
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'TipoOperacion',
        'Fecha',
        'Cantidad',
        'CostoPromedio',
        'CodigoLote',
        'CodigoDetalleIngreso',
        'CodigoDetalleSalida',
        'Stock'
    ];
}
