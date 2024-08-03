<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContratoProducto extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'contratoProducto';

    protected $fillable = [
        'Fecha',
        'Total',
        'TotalPagado',
        'CodigoPaciente',
        'CodigoSede',
        'CodigoTrabajador',
        'Vigente'
    ];


}
