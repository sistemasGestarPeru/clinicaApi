<?php

namespace App\Http\Controllers\API\AtencionCliente\Configuraciones;

use App\Http\Controllers\Controller;
use App\Models\AtencionCliente\Configuraciones\ColorOjos;
use App\Models\AtencionCliente\Configuraciones\MedioPublicitario;
use App\Models\AtencionCliente\Configuraciones\TexturaCabello;
use App\Models\AtencionCliente\Configuraciones\TonoPiel;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConfiguracionesController extends Controller
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


    // Color de Ojos
    public function listarColorOjos()
    {
        try {
            $entidad = ColorOjos::all();
            // Log de éxito
            Log::info('Listado de Ojos obtenidos correctamente', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'listarColorOjos',
                'cantidad' => count($entidad),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($entidad, 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error al listar Color Ojos', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'listarColorOjos',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Error al listar Color Ojos.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function registrarColorOjos(Request $request)
    {
        try {
            ColorOjos::create($request->all());
            // Log de éxito
            Log::info('Color de Ojos registrado correctamente', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'registrarColorOjos',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Color de Ojos registrado correctamente.'], 201);
        } catch (QueryException $e) {

            // Verificar si el error es por clave duplicada (código SQL 1062)
            if ($e->errorInfo[1] == 1062) {
                // Log del error de clave duplicada
                Log::warning('Error al registrar Color Ojos: clave duplicada', [
                    'Controlador' => 'ConfiguracionesController',
                    'Metodo' => 'registrarColorOjos',
                    'mensaje' => $e->getMessage(),
                    'linea' => $e->getLine(),
                    'archivo' => $e->getFile(),
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);

                return response()->json([
                    'error' => 'El Color de Ojos ya existe.'
                ], 500);
            }

            // Capturar otros errores de SQL

            Log::error('Error al registrar Color Ojos', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'registrarColorOjos',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['msg' => 'Error al registrar Color Ojos.', 'bd' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error al registrar Color Ojos', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'registrarColorOjos',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Error al listar Color Ojos.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function actualizarColorOjos(Request $request)
    {
        try {
            $colorOjos = ColorOjos::findOrFail($request->Codigo);
            $colorOjos->update($request->all());
            // Log de éxito
            Log::info('Color de Ojos actualizado correctamente', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'actualizarColorOjos',
                'codigo' => $colorOjos->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Color de Ojos actualizado correctamente.'], 200);
        } catch (\Exception $e) {
            // Log del error de actualización
            Log::error('Error al actualizar Color Ojos', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'actualizarColorOjos',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'codigo' => $request->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Error al actualizar Color Ojos.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function consultarColorOjos($codigo)
    {
        try {
            $colorOjos = ColorOjos::findOrFail($codigo);
            // Log de éxito
            Log::info('Color de Ojos consultado correctamente', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'consultarColorOjos',
                'codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($colorOjos, 200);
        } catch (\Exception $e) {
            // Log del error de consulta
            Log::error('Error al consultar Color Ojos', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'consultarColorOjos',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Error al consultar Color Ojos.', 'bd' => $e->getMessage()], 500);
        }
    }

    // Tono de Piel

    public function listarTonoPiel()
    {
        try {
            $entidad = TonoPiel::all();
            // Log de éxito
            Log::info('Listado de Tono de Piel obtenidos correctamente', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'listarTonoPiel',
                'cantidad' => count($entidad),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($entidad, 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error al listar Color Piel', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'listarTonoPiel',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Error al listar Color Piel.', 'bd' => $e->getMessage()], 500);
        }
    }
    public function registrarTonoPiel(Request $request)
    {
        try {
            TonoPiel::create($request->all());
            // Log de éxito
            Log::info('Color de Piel registrado correctamente', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'registrarTonoPiel',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Color de Piel registrado correctamente.'], 201);
        } catch (QueryException $e) {

            // Verificar si el error es por clave duplicada (código SQL 1062)
            if ($e->errorInfo[1] == 1062) {
                // Log del error de clave duplicada
                Log::warning('Error al registrar Color Piel: clave duplicada', [
                    'Controlador' => 'ConfiguracionesController',
                    'Metodo' => 'registrarTonoPiel',
                    'mensaje' => $e->getMessage(),
                    'linea' => $e->getLine(),
                    'archivo' => $e->getFile(),
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);

                return response()->json([
                    'error' => 'El Color de Piel ya existe.'
                ], 500);
            }

            // Capturar otros errores de SQL
            Log::error('Error al registrar Color Piel', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'registrarTonoPiel',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Error al registrar Color Piel.', 'bd' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            // Log del error de consulta
            Log::error('Error al registrar Color Piel', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'registrarTonoPiel',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Error al consultar Color Ojos.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function actualizarTonoPiel(Request $request)
    {
        try {
            $colorOjos = TonoPiel::findOrFail($request->Codigo);
            // Log de éxito
            Log::info('Color de Piel actualizado correctamente', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'actualizarTonoPiel',
                'codigo' => $colorOjos->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            $colorOjos->update($request->all());
            return response()->json(['msg' => 'Color de Piel actualizado correctamente.'], 200);
        } catch (\Exception $e) {
            // Log del error de actualización
            Log::error('Error al actualizar Color Piel', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'actualizarTonoPiel',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'codigo' => $request->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Error al actualizar Color Piel.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function consultarTonoPiel($codigo)
    {
        try {
            $colorOjos = TonoPiel::findOrFail($codigo);
            // Log de éxito
            Log::info('Color de Piel consultado correctamente', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'consultarTonoPiel',
                'codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($colorOjos, 200);
        } catch (\Exception $e) {
            // Log del error de consulta
            Log::error('Error al consultar Color Piel', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'consultarTonoPiel',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Error al consultar Color Piel.', 'bd' => $e->getMessage()], 500);
        }
    }

    // Textura de Cabello

    public function listarTexturaCabello()
    {
        try {
            $entidad = TexturaCabello::all();
            // Log de éxito
            Log::info('Listado de Textura de Cabello obtenidos correctamente', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'listarTexturaCabello',
                'cantidad' => count($entidad),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($entidad, 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error al listar Textura de Cabello', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'listarTexturaCabello',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Error al listar Textura de Cabello.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function registrarTexturaCabello(Request $request)
    {
        try {
            TexturaCabello::create($request->all());
            // Log de éxito
            Log::info('Textura de Cabello registrado correctamente', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'registrarTexturaCabello',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Textura de Cabello registrado correctamente.'], 201);
        } catch (QueryException $e) {

            // Verificar si el error es por clave duplicada (código SQL 1062)
            if ($e->errorInfo[1] == 1062) {
                // Log del error de clave duplicada
                Log::warning('Error al registrar Textura de Cabello: clave duplicada', [
                    'Controlador' => 'ConfiguracionesController',
                    'Metodo' => 'registrarTexturaCabello',
                    'mensaje' => $e->getMessage(),
                    'linea' => $e->getLine(),
                    'archivo' => $e->getFile(),
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json([
                    'error' => 'La Textura de Cabello ya existe.'
                ], 500);
            }

            // Capturar otros errores de SQL
            Log::error('Error al registrar Textura de Cabello', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'registrarTexturaCabello',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Error al registrar Textura de Cabello.', 'bd' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error al registrar Textura de Cabello', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'registrarTexturaCabello',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Error al registrar Textura de Cabello.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function actualizarTexturaCabello(Request $request)
    {
        try {
            $colorOjos = TexturaCabello::findOrFail($request->Codigo);
            $colorOjos->update($request->all());
            // Log de éxito
            Log::info('Textura de Cabello actualizado correctamente', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'actualizarTexturaCabello',
                'codigo' => $colorOjos->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Textura de Cabello actualizado correctamente.'], 200);
        } catch (\Exception $e) {
            // Log del error de actualización
            Log::error('Error al actualizar Textura de Cabello', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'actualizarTexturaCabello',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'codigo' => $request->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Error al actualizar Textura de Cabello.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function consultarTexturaCabello($codigo)
    {
        try {
            $colorOjos = TexturaCabello::findOrFail($codigo);
            // Log de éxito
            Log::info('Textura de Cabello consultado correctamente', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'consultarTexturaCabello',
                'codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($colorOjos, 200);
        } catch (\Exception $e) {
            // Log del error de consulta
            Log::error('Error al consultar Textura de Cabello', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'consultarTexturaCabello',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Error al consultar Textura de Cabello.', 'bd' => $e->getMessage()], 500);
        }
    }

    // Medio Publicitario: Modelo MedioPublicitario

    public function listarMedioPublicitario()
    {
        try {
            $entidad = MedioPublicitario::all();
            // Log de éxito
            Log::info('Listado de Medios Publicitarios obtenidos correctamente', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'listarMedioPublicitario',
                'cantidad' => count($entidad),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($entidad, 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error al listar Medio Publicitario', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'listarMedioPublicitario',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Error al listar Medio Publicitario.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function registrarMedioPublicitario(Request $request)
    {
        try {
            MedioPublicitario::create($request->all());
            // Log de éxito
            Log::info('Medio Publicitario registrado correctamente', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'registrarMedioPublicitario',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Medio Publicitario registrado correctamente.'], 201);
        } catch (QueryException $e) {

            // Verificar si el error es por clave duplicada (código SQL 1062)
            if ($e->errorInfo[1] == 1062) {
                // Log del error de clave duplicada
                Log::warning('Error al registrar Medio Publicitario: clave duplicada', [
                    'Controlador' => 'ConfiguracionesController',
                    'Metodo' => 'registrarMedioPublicitario',
                    'mensaje' => $e->getMessage(),
                    'linea' => $e->getLine(),
                    'archivo' => $e->getFile(),
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json([
                    'error' => 'El Medio Publicitario ya existe.'
                ], 500);
            }

            // Capturar otros errores de SQL
            Log::error('Error al registrar Medio Publicitario', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'registrarMedioPublicitario',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Error al registrar Medio Publicitario.', 'bd' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error al registrar Medio Publicitario', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'registrarMedioPublicitario',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Error al registrar Medio Publicitario.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function actualizarMedioPublicitario(Request $request)
    {
        try {
            $medioPublicitario = MedioPublicitario::findOrFail($request->Codigo);
            // Log de éxito
            Log::info('Medio Publicitario actualizado correctamente', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'actualizarMedioPublicitario',
                'codigo' => $medioPublicitario->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            $medioPublicitario->update($request->all());
            return response()->json(['msg' => 'Medio Publicitario actualizado correctamente.'], 200);
        } catch (\Exception $e) {
            // Log del error de actualización
            Log::error('Error al actualizar Medio Publicitario', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'actualizarMedioPublicitario',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'codigo' => $request->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Error al actualizar Medio Publicitario.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function consultarMedioPublicitario($codigo)
    {
        try {
            $medioPublicitario = MedioPublicitario::findOrFail($codigo);
            // Log de éxito
            Log::info('Medio Publicitario consultado correctamente', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'consultarMedioPublicitario',
                'codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($medioPublicitario, 200);
        } catch (\Exception $e) {
            // Log del error de consulta
            Log::error('Error al consultar Medio Publicitario', [
                'Controlador' => 'ConfiguracionesController',
                'Metodo' => 'consultarMedioPublicitario',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Error al consultar Medio Publicitario.', 'bd' => $e->getMessage()], 500);
        }
    }
}
