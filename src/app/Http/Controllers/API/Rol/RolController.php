<?php

namespace App\Http\Controllers\API\Rol;

use App\Http\Controllers\Controller;
use App\Models\Seguridad\Rol;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class RolController extends Controller
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

    public function listarRolesVigentes(){
        try{
            $roles = Rol::where('Vigente', 1)->get();
            return response()->json($roles, 200);
        }catch (\Exception $e) {
            // Capturar otros errores inesperados
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        }
    }

    public function listarRoles(){
        try{
            $roles = Rol::all();
            return response()->json($roles, 200);

        }catch (\Exception $e) {
            // Capturar otros errores inesperados
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        }
    }

    public function consultarRol($codigo){
        try{

            $rol = Rol::findOrFail($codigo);
            return response()->json($rol, 200);

        }catch (\Exception $e) {
            // Capturar otros errores inesperados
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        }
    }


    public function registroRol(Request $request){
        try{

            Rol::create($request->all());
            
            return response()->json(['message' => 'Rol registrado correctamente'], 201);

        }catch (QueryException $e) {
            // Verificar si el error es por clave duplicada (código SQL 1062)
            if ($e->errorInfo[1] == 1062) {
                return response()->json([
                    'error' => 'El Nombre del Rol ya existe. Intente con otro nombre.'
                ], 500);
            }
    
            // Capturar otros errores de SQL
            return response()->json([
                'error' => 'Error en la base de datos. Inténtelo nuevamente.'
            ], 500);
            
        } catch (\Exception $e) {
            // Capturar otros errores inesperados
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        }
    }

    public function actualizarRol(Request $request){
        try{
            
            $rol = Rol::findOrFail($request->Codigo);
            $rol->update($request->all());
            return response()->json(['message' => 'Rol actualizado correctamente'], 200);

        }catch (QueryException $e) {
            // Verificar si el error es por clave duplicada (código SQL 1062)
            if ($e->errorInfo[1] == 1062) {
                return response()->json([
                    'error' => 'El Nombre del Rol ya existe. Intente con otro nombre.'
                ], 500);
            }
    
            // Capturar otros errores de SQL
            return response()->json([
                'error' => 'Error en la base de datos. Inténtelo nuevamente.'
            ], 500);
            
        } catch (\Exception $e) {
            // Capturar otros errores inesperados
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        }
    }
}
