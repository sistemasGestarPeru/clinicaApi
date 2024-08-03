<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'producto';

    protected $fillable = [
        'Nombre',
        'Descripcion',
        'Monto',
        'Tipo',
        'TipoGravado',
        'Negociable',
        'MontoMinimo',
        'Vigente'
    ];
}
