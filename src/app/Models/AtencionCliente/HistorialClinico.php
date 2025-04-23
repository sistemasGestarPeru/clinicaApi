<?php

namespace App\Models\AtencionCliente;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialClinico extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'historialclinico';
    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'Numero',
        'CodigoPaciente01',
        'CodigoPaciente02'
    ];
}
