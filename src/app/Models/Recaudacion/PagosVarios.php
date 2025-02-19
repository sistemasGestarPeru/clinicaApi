<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagosVarios extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'pagosVarios';
    protected $primaryKey = 'Codigo';
    
    protected $fillable = [
        'Codigo',
        'CodigoReceptor',
        'Tipo',
        'Comentario',
        'Motivo',
        'Destino'
    ];
}
