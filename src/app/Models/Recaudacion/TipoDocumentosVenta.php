<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoDocumentosVenta extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'tipodocumentoventa';
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'Nombre',
        'CodigoSUNAT',
        'Vigente',
        'Tipo',
        'Siglas'
    ];

}
