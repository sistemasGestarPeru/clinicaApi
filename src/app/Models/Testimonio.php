<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testimonio extends Model
{
    use HasFactory;

    public function sede()
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }

    protected $fillable = [

        'nombre',
        'apellidoPaterno',
        'apellidoMaterno',
        'sede_id',
        'descripcion',
        'imagen',
        'vigente',

    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
