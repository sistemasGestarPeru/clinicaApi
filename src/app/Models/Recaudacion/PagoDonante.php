<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagoDonante extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'pagodonante';
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'Codigo',
        'CodigoDonante',
        'Comentario'
    ];

}
