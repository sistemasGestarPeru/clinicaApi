<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuentaProveedor extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'cuentaproveedor';

    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'Numero',
        'TipoMoneda',
        'CodigoProveedor',
        'CodigoEntidadBancaria',
        'Vigente'
    ];
}
