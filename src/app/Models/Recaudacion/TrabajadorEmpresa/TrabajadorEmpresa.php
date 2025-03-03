<?php

namespace App\Models\Recaudacion\TrabajadorEmpresa;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TrabajadorEmpresa extends Model
{
    public static function obtenerTrabajadorEmpresa($codigo){

        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d'); // Obtener la fecha actual en formato Y-m-d
        
        return DB::table('clinica_db.users as u')
                ->join('clinica_db.personas as p', 'u.CodigoPersona', '=', 'p.Codigo')
                ->join('clinica_db.trabajadors as t', 't.Codigo', '=', 'p.Codigo')
                ->join('clinica_db.contrato_laborals as cl', 'cl.CodigoTrabajador', '=', 't.Codigo')
                ->join('clinica_db.empresas as e', 'e.Codigo', '=', 'cl.CodigoEmpresa')
                ->where('u.CodigoPersona', $codigo)
                ->where('cl.Vigente', 1)
                ->where('t.Vigente', 1)
                ->where('e.Vigente', 1)
                ->where(function ($query) use ($fecha) {
                    $query->where(function ($q) use ($fecha) {
                        $q->where('cl.FechaInicio', '<=', $fecha)
                            ->where(function ($subQuery) use ($fecha) {
                                $subQuery->where('cl.FechaFin', '>=', $fecha)
                                    ->orWhereNull('cl.FechaFin');
                            });
                    });
                })
                ->select('e.Codigo as id', 'e.Nombre as nombre')
                ->get();
    }
}
