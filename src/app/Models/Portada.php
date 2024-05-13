<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Portada extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [

        'imagenEsc',
        'imagenCel',
        'TextoBtn',
        'UrlBtn',
        'vigente',
        'identificadorPadre',
        'identificadorHijo'
    ];
}
