<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medico extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function sede()
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }

    protected $fillable = [
        'nombre',
        'apellidoPaterno',
        'apellidoMaterno',
        'genero',
        'imagen',
        'linkedin',
        'descripcion',
        'CMP',
        'RNE',
        'CBP',
        'tipo',
        'sede_id',
        'vigente',
    ];
}
