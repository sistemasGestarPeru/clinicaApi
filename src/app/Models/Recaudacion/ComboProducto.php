<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComboProducto extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'productocombo';

    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'CodigoCombo',
        'CodigoProducto',
        'Cantidad',
        'Precio',
        'Vigente'
    ];
}
