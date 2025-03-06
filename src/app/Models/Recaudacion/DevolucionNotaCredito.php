<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DevolucionNotaCredito extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'devolucionnotacredito';

    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'Codigo',
        'CodigoDocumentoVenta'
    ];

}
