<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleCertificado extends Model
{
    use HasFactory;

    protected $table = 'detallecertificacion';
    protected $primaryKey = 'Codigo';
    public $timestamps = false;

    protected $fillable = [
        'CodigoMedico',
        'Nombre',
        'Institucion',
        'FechaEmision',
        'FechaCaducidad',
        'Logo',
        'Descripcion',
        'Vigente',
    ];


    public function medico()
    {
        return $this->belongsTo(Medico::class, 'CodigoMedico', 'id');
    }
}
