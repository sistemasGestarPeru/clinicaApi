<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'proveedor';

    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'RazonSocial',
        'RUC',
        'Celular',
        'Correo',
        'Vigente'
    ];
}
