<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sede extends Model
{
    use HasFactory;

    public function testimonios()
    {
        return $this->hasMany(Testimonio::class, 'id');
    }

    protected $fillable = [

        'nombre'

    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
