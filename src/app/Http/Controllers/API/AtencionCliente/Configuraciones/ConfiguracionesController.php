<?php

namespace App\Http\Controllers\API\AtencionCliente\Configuraciones;

use App\Http\Controllers\Controller;
use App\Models\AtencionCliente\Configuraciones\ColorOjos;
use App\Models\AtencionCliente\Configuraciones\MedioPublicitario;
use App\Models\AtencionCliente\Configuraciones\TexturaCabello;
use App\Models\AtencionCliente\Configuraciones\TonoPiel;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

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
            return response()->json($entidad, 200);
        } catch (\Exception $e) {
            return response()->json(['msg' => 'Error al listar Color Ojos.' ,'bd' => $e->getMessage()], 500);
        }
    }

    public function registrarColorOjos(Request $request)
    {
        try {
            ColorOjos::create($request->all());
            return response()->json(['msg' => 'Color de Ojos registrado correctamente.'], 201);
        } catch (QueryException $e) {

            // Verificar si el error es por clave duplicada (código SQL 1062)
            if ($e->errorInfo[1] == 1062) {
                return response()->json([
                    'error' => 'El Color de Ojos ya existe.'
                ], 500);
            }

            // Capturar otros errores de SQL

            return response()->json(['msg' => 'Error al registrar Color Ojos.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function actualizarColorOjos(Request $request)
    {
        try {
            $colorOjos = ColorOjos::findOrFail($request->Codigo);
            $colorOjos->update($request->all());
            return response()->json(['msg' => 'Color de Ojos actualizado correctamente.'], 200);
        } catch (QueryException $e) {
            return response()->json(['msg' => 'Error al actualizar Color Ojos.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function consultarColorOjos($codigo)
    {
        try {
            $colorOjos = ColorOjos::findOrFail($codigo);
            return response()->json($colorOjos, 200);
        } catch (QueryException $e) {
            return response()->json(['msg' => 'Error al consultar Color Ojos.', 'bd' => $e->getMessage()], 500);
        }
    }

    // Tono de Piel

    public function listarTonoPiel()
    {
        try {
            $entidad = TonoPiel::all();
            return response()->json($entidad, 200);
        } catch (\Exception $e) {
            return response()->json(['msg' => 'Error al listar Color Piel.' ,'bd' => $e->getMessage()], 500);
        }
    }
    public function registrarTonoPiel(Request $request)
    {
        try {
            TonoPiel::create($request->all());
            return response()->json(['msg' => 'Color de Piel registrado correctamente.'], 201);
        } catch (QueryException $e) {

            // Verificar si el error es por clave duplicada (código SQL 1062)
            if ($e->errorInfo[1] == 1062) {
                return response()->json([
                    'error' => 'El Color de Piel ya existe.'
                ], 500);
            }

            // Capturar otros errores de SQL

            return response()->json(['msg' => 'Error al registrar Color Piel.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function actualizarTonoPiel(Request $request)
    {
        try {
            $colorOjos = TonoPiel::findOrFail($request->Codigo);
            $colorOjos->update($request->all());
            return response()->json(['msg' => 'Color de Piel actualizado correctamente.'], 200);
        } catch (QueryException $e) {
            return response()->json(['msg' => 'Error al actualizar Color Piel.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function consultarTonoPiel($codigo)
    {
        try {
            $colorOjos = TonoPiel::findOrFail($codigo);
            return response()->json($colorOjos, 200);
        } catch (QueryException $e) {
            return response()->json(['msg' => 'Error al consultar Color Piel.', 'bd' => $e->getMessage()], 500);
        }
    }

    // Textura de Cabello

    public function listarTexturaCabello()
    {
        try {
            $entidad = TexturaCabello::all();
            return response()->json($entidad, 200);
        } catch (\Exception $e) {
            return response()->json(['msg' => 'Error al listar Textura de Cabello.' ,'bd' => $e->getMessage()], 500);
        }
    }

    public function registrarTexturaCabello(Request $request)
    {
        try {
            TexturaCabello::create($request->all());
            return response()->json(['msg' => 'Textura de Cabello registrado correctamente.'], 201);
        } catch (QueryException $e) {

            // Verificar si el error es por clave duplicada (código SQL 1062)
            if ($e->errorInfo[1] == 1062) {
                return response()->json([
                    'error' => 'La Textura de Cabello ya existe.'
                ], 500);
            }

            // Capturar otros errores de SQL

            return response()->json(['msg' => 'Error al registrar Textura de Cabello.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function actualizarTexturaCabello(Request $request)
    {
        try {
            $colorOjos = TexturaCabello::findOrFail($request->Codigo);
            $colorOjos->update($request->all());
            return response()->json(['msg' => 'Textura de Cabello actualizado correctamente.'], 200);
        } catch (QueryException $e) {
            return response()->json(['msg' => 'Error al actualizar Textura de Cabello.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function consultarTexturaCabello($codigo)
    {
        try {
            $colorOjos = TexturaCabello::findOrFail($codigo);
            return response()->json($colorOjos, 200);
        } catch (QueryException $e) {
            return response()->json(['msg' => 'Error al consultar Textura de Cabello.', 'bd' => $e->getMessage()], 500);
        }
    }
    
    // Medio Publicitario: Modelo MedioPublicitario

    public function listarMedioPublicitario()
    {
        try {
            $entidad = MedioPublicitario::all();
            return response()->json($entidad, 200);
        } catch (\Exception $e) {
            return response()->json(['msg' => 'Error al listar Medio Publicitario.' ,'bd' => $e->getMessage()], 500);
        }
    }

    public function registrarMedioPublicitario(Request $request)
    {
        try {
            MedioPublicitario::create($request->all());
            return response()->json(['msg' => 'Medio Publicitario registrado correctamente.'], 201);
        } catch (QueryException $e) {

            // Verificar si el error es por clave duplicada (código SQL 1062)
            if ($e->errorInfo[1] == 1062) {
                return response()->json([
                    'error' => 'El Medio Publicitario ya existe.'
                ], 500);
            }

            // Capturar otros errores de SQL

            return response()->json(['msg' => 'Error al registrar Medio Publicitario.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function actualizarMedioPublicitario(Request $request)
    {
        try {
            $medioPublicitario = MedioPublicitario::findOrFail($request->Codigo);
            $medioPublicitario->update($request->all());
            return response()->json(['msg' => 'Medio Publicitario actualizado correctamente.'], 200);
        } catch (QueryException $e) {
            return response()->json(['msg' => 'Error al actualizar Medio Publicitario.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function consultarMedioPublicitario($codigo)
    {
        try {
            $medioPublicitario = MedioPublicitario::findOrFail($codigo);
            return response()->json($medioPublicitario, 200);
        } catch (QueryException $e) {
            return response()->json(['msg' => 'Error al consultar Medio Publicitario.', 'bd' => $e->getMessage()], 500);
        }
    }

}
