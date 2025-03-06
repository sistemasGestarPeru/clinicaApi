<?php

namespace App\Models\Recaudacion\ValidacionCaja;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class MontoCaja extends Model
{
    protected $table = 'caja';
    public static function obtenerTotalCaja($caja)
    {
        return DB::table(DB::raw('(SELECT 
            (COALESCE((SELECT SUM(Monto) FROM ingresodinero WHERE CodigoCaja = ? AND Vigente = 1), 0) 
            - COALESCE((SELECT SUM(e.Monto) FROM egreso AS e 
                        JOIN medioPago AS mp ON mp.Codigo = e.CodigoMedioPago 
                        WHERE e.CodigoCaja = ? 
                        AND mp.CodigoSUNAT = "008" 
                        AND e.Vigente = 1 
                        AND e.Codigo NOT IN (SELECT Codigo FROM salidadinero)), 0)
            + COALESCE((SELECT SUM(pag.Monto) FROM pago AS pag 
                        JOIN medioPago AS mp ON mp.Codigo = pag.CodigoMedioPago 
                        WHERE pag.CodigoCaja = ? 
                        AND pag.Vigente = 1 
                        AND mp.CodigoSUNAT = "008"), 0)
            - COALESCE((SELECT SUM(e.Monto) FROM salidadinero AS sdd 
                        JOIN egreso AS e ON e.Codigo = sdd.Codigo 
                        JOIN medioPago AS mp ON mp.Codigo = e.CodigoMedioPago 
                        WHERE e.CodigoCaja = ? 
                        AND e.Vigente = 1 
                        AND mp.CodigoSUNAT = "008"), 0)
            ) AS Total) AS subquery'))
            ->select('Total')
            ->setBindings([$caja, $caja, $caja, $caja])
            ->value('Total');
    }
}


