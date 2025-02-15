<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Anulacion extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'anulacion';
    protected $primaryKey = 'Codigo';
    protected $fillable = [

        'Fecha',
        'CodigoDocumentoVenta',
        'CodigoMotivo',
        'CodigoTrabajador',
        'Comentario'
    ];
}
