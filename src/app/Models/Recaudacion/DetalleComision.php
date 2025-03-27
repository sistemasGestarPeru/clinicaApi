<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleComision extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'detallecomision';

    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'Monto',
        'CodigoComision',
        'CodigoDetalleVenta',
        'CodigoDetalleContrato'
    ];
}
