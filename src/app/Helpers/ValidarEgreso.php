<?php

namespace App\Helpers;

use App\Models\Recaudacion\ValidacionCaja\MontoCaja;
use App\Models\Recaudacion\ValidacionCaja\ValidarFecha;
use Carbon\Carbon;

class ValidarEgreso
{
    /**
     * Valida fechas y reglas del egreso antes de guardar o actualizar.
     * Retorna un array con los datos normalizados o lanza una excepción.
     */
    public static function validar(array $egreso, ?array $servicio = null)
    {
        // 📅 Validar fechas
        $fechaCajaObj = ValidarFecha::obtenerFechaCaja($egreso['CodigoCaja']);
        $fechaCaja = Carbon::parse($fechaCajaObj->FechaInicio)->toDateString();
        $fechaEgreso = Carbon::parse($egreso['Fecha'])->toDateString();

        if ($fechaCaja < $fechaEgreso) {
            throw new \Exception(__('mensajes.error_fecha_pago'));
        }

        if ($servicio && isset($servicio['FechaDocumento'])) {
            $fechaServicio = Carbon::parse($servicio['FechaDocumento'])->toDateString();
            if ($fechaCaja < $fechaServicio) {
                throw new \Exception(__('mensajes.error_fecha_pago'));
            }
        }

        // 🧹 Normalizar campos nulos
        $egreso['CodigoCuentaOrigen'] = $egreso['CodigoCuentaOrigen'] ?? null;
        $egreso['CodigoBilleteraDigital'] = $egreso['CodigoBilleteraDigital'] ?? null;

        // 💰 Reglas según CódigoSUNAT
        switch ($egreso['CodigoSUNAT']) {
            case '008': // Efectivo
                $egreso = self::limpiarCamposEfectivo($egreso);
                $total = MontoCaja::obtenerTotalCaja($egreso['CodigoCaja']);
                if ($egreso['Monto'] > $total) {
                    throw new \Exception(
                        __('mensajes.error_sin_efectivo', ['total' => $total])
                    );
                }
                break;

            case '003': // Tarjeta
                $egreso['Lote'] = null;
                $egreso['Referencia'] = null;
                break;

            case '005': // Transferencia
            case '006': // Billetera digital
                $egreso['CodigoCuentaBancaria'] = null;
                $egreso['CodigoBilleteraDigital'] = null;
                break;
        }

        return $egreso;
    }

    /**
     * Limpia campos no usados para pagos en efectivo.
     */
    private static function limpiarCamposEfectivo(array $egreso): array
    {
        $egreso['CodigoCuentaOrigen'] = null;
        $egreso['CodigoBilleteraDigital'] = null;
        $egreso['Lote'] = null;
        $egreso['Referencia'] = null;
        $egreso['NumeroOperacion'] = null;

        return $egreso;
    }
}
