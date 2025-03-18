<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'rol';
    protected $primaryKey = 'Codigo';
    
    protected $fillable = [
        'Nombre',
        'Vigente'
    ];
}
