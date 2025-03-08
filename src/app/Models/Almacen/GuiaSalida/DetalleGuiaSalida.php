<?php

namespace App\Models\Almacen\GuiaSalida;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleGuiaSalida extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'detalleguiasalida';
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'Cantidad',
        'Costo',
        'CodigoGuiaSalida',
        'CodigoProducto'
    ];
}
