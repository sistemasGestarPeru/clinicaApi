<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promocion extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [

        'titulo',
        'fecha_inicio',
        'fecha_fin',
        'imagen',
        'descripcion',
        'file',
        'sedes',
        'vigente',

    ];
}
