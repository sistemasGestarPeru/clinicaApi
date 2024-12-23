<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagoServicio extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'pagoservicio';
    protected $primaryKey = 'Codigo';
    
    protected $fillable = [
        'Codigo',
        'CodigoMotivoPago',
        'Descripcion',
        'Documento'
    ];
}
