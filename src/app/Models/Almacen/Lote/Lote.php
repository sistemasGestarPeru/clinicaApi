<?php

namespace App\Models\Almacen\Lote;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lote extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'lote';
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'Serie',
        'Cantidad',
        'Stock',
        'Costo',
        'MontoIGV',
        'FechaCaducidad',
        'CodigoProducto',
        'CodigoSede',
        'CodigoDetalleIngreso',
        'Vigente'
    ];
}
