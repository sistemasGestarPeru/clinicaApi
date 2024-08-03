<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompromisoContrato extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'compromisocontrato';

    protected $fillable = [
        'Fecha',
        'Monto',
        'CodigoContrato',
        'Vigente'
    ];
}
