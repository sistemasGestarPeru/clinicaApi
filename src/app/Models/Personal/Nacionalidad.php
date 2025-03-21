<?php

namespace App\Models\Personal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nacionalidad extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'nacionalidads';
    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'Nombre',
        'Vigente',
    ];
}

