<?php

namespace App\Models\AtencionCliente;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Varon extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'varon';
    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'Codigo',
        'IntentosPrevios',
        'CuantoTiempo',
        'EdadesHijosActual',
        'PruebasSemen',
        'TestGenetico',
        'EnfermedadViral'
    ];
}
