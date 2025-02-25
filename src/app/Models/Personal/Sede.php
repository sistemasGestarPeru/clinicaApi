<?php

namespace App\Models\Personal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sede extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'sedesrec';
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'Nombre',
        'Direccion',
        'Telefono',
        'CodigoEmpresa',
        'CodigoDepartamento',
        'Vigente'
    ];
}
