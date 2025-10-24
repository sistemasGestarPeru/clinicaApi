<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContratoProducto extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'contratoproducto';
    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'NumContrato',
        'Fecha',
        'Total',
        'TotalPagado',
        'CodigoPaciente',
        'CodigoClienteEmpresa',
        'CodigoSede',
        'CodigoTrabajador',
        'Vigente',
        'CodigoMedico',
        'CodigoPaciente02',
        'CodigoAutorizador',
        'CodigoContratoReferencia'
    ];
}
