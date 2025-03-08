<?php

namespace App\Models\Almacen\GuiaIngreso;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleGuiaIngreso extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'detalleguiaingreso';
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'Cantidad',
        'Costo',
        'CodigoGuiaRemision',
        'CodigoProducto'
    ];
}
