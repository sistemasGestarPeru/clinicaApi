<?php

namespace App\Models\AtencionCliente;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'horario';
    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'Fecha',
        'HoraInicio',
        'HoraFin',
        'CodigoMedico',
        'CodigoSede',
        'Vigente'
    ];
}
