<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngresoDinero extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'ingresodinero';
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'Fecha',
        'Monto',
        'Tipo',
        'CodigoCaja',
        'CodigoEmisor',
        'Vigente'
    ];
}
