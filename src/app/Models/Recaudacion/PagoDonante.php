<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagoDonante extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'PagoDonante';
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'Codigo',
        'CodigoDonante',
        'Comentario'
    ];

}
