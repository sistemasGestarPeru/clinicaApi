<?php


namespace App\Models\Recaudacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntidadBancaria extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'entidadbancaria';
    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'Nombre',
        'Siglas',
        'Vigente'
    ];
}
