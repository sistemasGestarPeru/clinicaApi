<?php


namespace App\Models\Recaudacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BilleteraDigital extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'entidadbilleteradigital';
    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'Nombre',
        'Vigente'
    ];
}
