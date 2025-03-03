<?php

namespace App\Models\Recaudacion\ValidacionCaja;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ValidarFecha extends Model
{

    protected $table = 'Caja';
    public static function obtenerFechaCaja($caja)
    {
        return DB::table(DB::raw("(SELECT DATE(FechaInicio) as FechaInicio FROM caja WHERE Codigo = ?) as subquery"))
            ->setBindings([$caja]) // Evita SQL Injection
            ->select('FechaInicio')
            ->first();
    }
}

