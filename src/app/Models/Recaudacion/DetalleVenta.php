<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleVenta extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'DetalleDocumentoVenta';
    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'Numero',
        'Descripcion',
        'Cantidad',
        'TipoGravado',
        'MontoTotal',
        'MontoIGV',
        'CodigoVenta',
        'CodigoProducto'

    ];
}
