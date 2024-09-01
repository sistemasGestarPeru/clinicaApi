<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocalMedioPago extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'LocalMedioPago';
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'CodigoSede',
        'CodigoMedioPago',
        'Vigente'
    ];
}
