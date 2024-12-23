<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalidaDinero extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'salidadinero';

    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'Codigo',
        'CodigoCuentaBancaria',
        'CodigoReceptor'
    ];
}
