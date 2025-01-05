<?php

namespace App\Models\Personal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trabajador extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'Codigo',
        'CorreoCoorporativo',
        'FechaNacimiento',
        'CodigoSistemaPensiones',
        'AutorizaDescuento',
        'Vigente',
    ];
}
