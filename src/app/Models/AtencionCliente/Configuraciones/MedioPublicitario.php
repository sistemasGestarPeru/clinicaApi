<?php

namespace App\Models\AtencionCliente\Configuraciones;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedioPublicitario extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'mediopublicitario';
    protected $primaryKey = 'Codigo';
    
    protected $fillable = [
        'Nombre',
        'Vigente'
    ];
}
