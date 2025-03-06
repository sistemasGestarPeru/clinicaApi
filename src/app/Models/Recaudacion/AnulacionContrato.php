<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnulacionContrato extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'anulacioncontrato';
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'Codigo',
        'Fecha',
        'CodigoMotivo',
        'CodigoTrabajador',
        'Comentario'
    ];
}
