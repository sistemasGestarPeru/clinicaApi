<?php

namespace App\Models\Personal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoDocumento extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'tipo_documentos';
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'Nombre',
        'Siglas',
        'CodigoSUNAT',
        'Vigente'
    ];
}
