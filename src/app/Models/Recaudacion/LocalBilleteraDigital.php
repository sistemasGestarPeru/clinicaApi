<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocalBilleteraDigital extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'billeteradigital';
    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'CodigoEntidadBilleteraDigital',
        'CodigoEmpresa',
        'Numero',
        'Vigente'
    ];
}
