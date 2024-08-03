<?php

namespace App\Models\Personal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'Nombre',
        'RazonSocial',
        'RUC',
        'Direccion',
        'Correo',
        'Vigente'
    ];
}
