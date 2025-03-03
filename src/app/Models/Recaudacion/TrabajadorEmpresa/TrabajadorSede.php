<?php

namespace App\Models\Recaudacion\TrabajadorEmpresa;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TrabajadorSede extends Model
{
    public static function obtenerTrabajadorSede($codigo, $codigoEmpresa){
        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d'); // Obtener la fecha actual en formato Y-m-d

        return DB::table('clinica_db.users as u')
            ->join('clinica_db.personas as p', 'u.CodigoPersona', '=', 'p.Codigo')
            ->join('clinica_db.trabajadors as t', 't.Codigo', '=', 'p.Codigo')
            ->join('clinica_db.asignacion_sedes as ase', 'ase.CodigoTrabajador', '=', 't.Codigo')
            ->join('clinica_db.sedesrec as s', 's.Codigo', '=', 'ase.CodigoSede')
            ->join('clinica_db.empresas as e', 'e.Codigo', '=', 's.CodigoEmpresa')
            ->where('u.CodigoPersona', $codigo)
            ->where('e.Codigo', $codigoEmpresa)
            ->where('t.Vigente', 1)
            ->where('ase.Vigente', 1)
            ->where('s.Vigente', 1)
            ->where('e.Vigente', 1)
            ->where(function ($query) use ($fecha) {
                $query->where(function ($q) use ($fecha) {
                    $q->where('ase.FechaInicio', '<=', $fecha)
                        ->where(function ($subQuery) use ($fecha) {
                            $subQuery->where('ase.FechaFin', '>=', $fecha)
                                ->orWhereNull('ase.FechaFin');
                        });
                });
            })
            ->select('s.Codigo as id', 's.Nombre as nombre')
            ->get();

    }
}


