<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngresoDinero extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'ingresodinero';

    protected $fillable = [
        'Fecha',
        'Monto',
        'Tipo',
        'CodigoCaja',
        'Vigente'
    ];
}
