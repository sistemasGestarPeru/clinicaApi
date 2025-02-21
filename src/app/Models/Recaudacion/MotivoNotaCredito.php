<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MotivoNotaCredito extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'motivonotacredito';
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'Nombre',
        'CodigoSUNAT',
        'Vigente'
    ];
}
