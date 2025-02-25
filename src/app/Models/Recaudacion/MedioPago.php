<?php


namespace App\Models\Recaudacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedioPago extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'mediopago';
    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'Nombre',
        'CodigoSUNAT',
        'Vigente'
    ];
}
