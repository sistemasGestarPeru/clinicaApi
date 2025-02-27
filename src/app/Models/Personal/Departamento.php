<?php

namespace App\Models\Personal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'departamentos';
    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'Nombre',
        'Vigente'
    ];
}
