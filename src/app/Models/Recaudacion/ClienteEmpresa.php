<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteEmpresa extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'Codigo';
    protected $table = 'clienteempresa';

    protected $fillable = [
        'RazonSocial',
        'RUC',
        'Direccion',
        'CodigoDepartamento',
        'Vigente'
    ];
}
