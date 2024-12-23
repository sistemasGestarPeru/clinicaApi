<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cuota extends Model
{
    use HasFactory;

    
    public $timestamps = false;

    protected $table = 'cuota';

    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'CodigoCompra',
        'Monto',
        'TipoMoneda',
        'Fecha',
        'Vigente'
    ];
}
