<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    protected $fillable = [
        'Titulo',
        'Fecha',
        'Imagen',
        'Descripcion',
        'vigente',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
