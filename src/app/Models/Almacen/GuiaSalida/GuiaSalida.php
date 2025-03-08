<?php

namespace App\Models\Almacen\GuiaSalida;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuiaSalida extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'guiasalida';
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'TipoDocumento',
        'Serie',
        'Numero',
        'Fecha',
        'Motivo',
        'CodigoTrabajador',
        'CodigoSede',
        'CodigoVenta',
        'Vigente'
    ];
}
