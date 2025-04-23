<?php

namespace App\Models\AtencionCliente;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'paciente';
    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'Codigo',
        'CodigoColorOjos',
        'CodigoTonoPiel',
        'CodigoTexturaCabello',
        'CodigoMedioPublicitario',
        'CodigoPais',
        'Motivo',
        'Genero',
        'FechaRegistro',
        'EstadoCivil',
        'FechaNacimiento',
        'Ciudad',
        'Profesion',
        'GrupoSanguineo',
        'RH',
        'Peso',
        'Altura',
        'OperacionesQuirurgicas',
        'Alergias',
        'SustanciasToxicas',
        'CantidadCigarrosDia',
        'CantidadAlcoholDia',
        'Diabetes',
        'Medicacion',
        'EdadesHijos',
        'TensionAlta',
        'EnfermedadesGeneticas',
        'Cancer',
        'OtrasEnfermedades'
    ];

}
