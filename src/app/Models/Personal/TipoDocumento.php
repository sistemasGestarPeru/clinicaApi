<?php

namespace App\Models\Personal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoDocumento extends Model
{
    use HasFactory;
    public $timestamps = false;

    //protected $table = 'tipodocumento';

    protected $fillable = [
        'Nombre',
        'Siglas',
        'CodigoSUNAT',
        'Vigente'
    ];
}
