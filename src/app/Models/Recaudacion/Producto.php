<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'producto';
    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'CodigoCategoria',
        'Nombre',
        'Descripcion',
        'Tipo',
        'Vigente',
        'CodigoUnidadMedida'
    ];
}
