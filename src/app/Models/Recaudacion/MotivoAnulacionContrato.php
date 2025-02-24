<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MotivoAnulacionContrato extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'motivoanulacioncontrato';
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'Nombre',
        'Descripcion',
        'Vigente'
    ];

}
