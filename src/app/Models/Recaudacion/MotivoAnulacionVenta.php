<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MotivoAnulacionVenta extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'motivoanulacion';
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'Nombre',
        'Descripcion',
        'Vigente'
    ];
}
