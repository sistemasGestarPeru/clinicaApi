<?php


namespace App\Models\Recaudacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoGravado extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'tipogravado';
    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'Tipo',
        'Nombre',
        'CodigoSUNAT',
        'Porcentaje',
        'Gratis',
        'Vigente'
    ];
}
