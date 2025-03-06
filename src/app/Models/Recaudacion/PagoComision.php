<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagoComision extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'pagocomision';
    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'Codigo',
        'CodigoMedico',
        'Comentario',
        'CodigoContrato',
        'CodigoDocumentoVenta'
    ];
}
