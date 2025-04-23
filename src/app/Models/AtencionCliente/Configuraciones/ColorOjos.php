<?php

namespace App\Models\AtencionCliente\Configuraciones;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ColorOjos extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'colorojos';
    protected $primaryKey = 'Codigo';
    
    protected $fillable = [
        'Nombre',
        'Vigente'
    ];
}
