<?php

namespace App\Models\AtencionCliente;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmbarazoPrevio extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'embarazoprevio';
    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'Fecha',
        'Normal',
        'Aborto',
        'Ectopico',
        'ConActual',
        'ConTratamiento',
        'Complicaciones',
        'CodigoMujer',
    ];
}
