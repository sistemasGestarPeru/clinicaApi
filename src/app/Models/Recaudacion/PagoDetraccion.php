<?php

namespace App\Models\Recaudacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagoDetraccion extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'pagodetraccion';
    protected $primaryKey = 'Codigo';
    protected $fillable = [
        'Codigo',
        'CodigoCuentaDetraccion'
    ];
}
