<?php

namespace App\Http\Controllers\API\SedeProducto;

use App\Http\Controllers\Controller;
use App\Http\Requests\SedeProducto\RegistrarSedeProductoRequest;
use App\Models\Recaudacion\SedeProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SedeProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function actualizarProductoSede(Request $request)
    {
        try {
            $entidad = SedeProducto::find($request->input('Codigo'));
            $entidad->update($request->all());

            //log info
            Log::info('Producto actualizado en la sede', [
                'Controlador' => 'SedeProductoController',
                'Metodo' => 'actualizarProductoSede',
                'Comando' => $request->all(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['message' => 'Producto actualizado correctamente en la sede.'], 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al actualizar producto en la sede', [
                'Controlador' => 'SedeProductoController',
                'Metodo' => 'actualizarProductoSede',
                'Comando' => $request->all(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarProductoSede($codigo)
    {

        try {
            // Buscar sedeProducto por su código
            $sedeProducto = SedeProducto::find($codigo);

            // Verificar si se encontró la sedeProducto
            if (!$sedeProducto) {

                //log warning
                Log::warning('Producto no encontrado en la sede', [
                    'Controlador' => 'SedeProductoController',
                    'Metodo' => 'consultarProductoSede',
                    'Codigo' => $codigo,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);

                return response()->json(['error' => 'Producto no encontrado en la sede.'], 404);
            }

            // Buscar datos del producto asociado
            $productoData = DB::table('producto')
                ->select(
                    'Codigo as CodigoProducto',
                    'Nombre',
                    DB::raw("CASE 
                                WHEN Tipo = 'S' THEN 'SERVICIO' 
                                WHEN Tipo = 'B' THEN 'BIEN' 
                                WHEN Tipo = 'C' THEN 'COMBO' 
                            END AS TipoProducto")
                )
                ->where('Codigo', $sedeProducto->CodigoProducto)
                ->first(); // Para obtener un solo resultado

            //log info
            Log::info('Producto consultado en la sede', [
                'Controlador' => 'SedeProductoController',
                'Metodo' => 'consultarProductoSede',
                'Codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json([
                'producto' => $productoData,
                'sedeProducto' => $sedeProducto
            ]);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar producto en la sede', [
                'Controlador' => 'SedeProductoController',
                'Metodo' => 'consultarProductoSede',
                'Codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listarProductosNoAsignados()
    {

        try {

            $productos = DB::table('producto as p')
                ->leftJoin('sedeproducto as sp', 'p.Codigo', '=', 'sp.CodigoProducto')
                ->select(
                    'p.Codigo as CodigoProducto',
                    'p.Nombre',
                    DB::raw("
                    CASE
                        WHEN p.Tipo = 'S' THEN 'SERVICIO'
                        WHEN p.Tipo = 'B' THEN 'BIEN'
                        WHEN p.tipo = 'C' THEN 'COMBO'
                    END AS TipoProducto
                "),
                    'p.tipo as Tipo'
                )
                ->where('p.Vigente', 1)
                ->whereNull('sp.Codigo')
                ->get();

            //log info
            Log::info('Listar Productos No Asignados', [
                'Controlador' => 'SedeProductoController',
                'Metodo' => 'listarProductosNoAsignados',
                'Cantidad' => $productos->count(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($productos, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al listar productos no asignados', [
                'Controlador' => 'SedeProductoController',
                'Metodo' => 'listarProductosNoAsignados',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listarSedeProducto($sede, $codProd)
    {

        if ($codProd == 0) {
            $codProd = null;
        }

        try {
            $productos = DB::table('producto as p')
                ->join('sedeproducto as sp', 'p.Codigo', '=', 'sp.CodigoProducto')
                ->join('tipogravado as tg', 'sp.CodigoTipoGravado', '=', 'tg.Codigo')
                ->join('sedesrec as s', 's.Codigo', '=', 'sp.CodigoSede')
                ->select(
                    'sp.Codigo as CodProdSede',
                    'p.Codigo as CodProd',
                    's.Nombre as Sede',
                    'p.Nombre as Producto',
                    'sp.Precio',
                    'sp.Stock',
                    'tg.Tipo as TipoGravado',
                    'sp.Vigente'
                )
                ->where('sp.CodigoSede', $sede)
                ->when($codProd, function ($query, $codProd) {
                    return $query->where('sp.CodigoProducto', $codProd);
                })
                ->get();

            //log info
            Log::info('Listar Productos por Sede', [
                'Controlador' => 'SedeProductoController',
                'Metodo' => 'listarSedeProducto',
                'Sede' => $sede,
                'CodigoProducto' => $codProd,
                'Cantidad' => $productos->count(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($productos, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al listar productos por sede', [
                'Controlador' => 'SedeProductoController',
                'Metodo' => 'listarSedeProducto',
                'Sede' => $sede,
                'CodigoProducto' => $codProd,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarProductoSede(Request $request)
    {
        $sedeProductos = $request->input('sedeProductos');

        foreach ($sedeProductos as $sedeProducto) {

            if ($sedeProducto['Tipo'] == 'COMBO') {
                $existe = !DB::table('productocombo as pc')
                    ->where('pc.CodigoCombo', $sedeProducto['CodigoProducto'])
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('sedeproducto as sp')
                            ->whereColumn('sp.CodigoProducto', 'pc.CodigoProducto');
                    })
                    ->exists();

                if (!$existe) {

                    //log warning
                    Log::warning('No se puede registrar el combo', [
                        'Controlador' => 'SedeProductoController',
                        'Metodo' => 'registrarProductoSede',
                        'CodigoCombo' => $sedeProducto['CodigoProducto'],
                        'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                    ]);

                    return response()->json(['error' => 'No se puede registrar el combo, uno o más productos no están registrados en la sede.'], 500);
                }
            }
        }

        try {

            $dataValidar = Validator::make(
                ['sedeProductos' => $sedeProductos],
                (new RegistrarSedeProductoRequest())->rules(),
            );
            $dataValidar->validate();

            //Registrar Detalle

            foreach ($sedeProductos as $sedeProducto) {
                SedeProducto::create($sedeProducto);
            }

            //log info
            Log::info('Productos registrados en la sede', [
                'Controlador' => 'SedeProductoController',
                'Metodo' => 'registrarProductoSede',
                'sedeProductos' => $sedeProductos,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['message' => 'Productos registrados correctamente en la sede.'], 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al registrar productos en la sede', [
                'Controlador' => 'SedeProductoController',
                'Metodo' => 'registrarProductoSede',
                'sedeProductos' => $sedeProductos,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json(['error' => 'Ocurrió un error inesperado.', 'detalle' => $e->getMessage()], 500);
        }
    }
}
