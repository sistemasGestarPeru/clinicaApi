<?php

namespace App\Http\Controllers\API\Rol;

use App\Http\Controllers\Controller;
use App\Models\Seguridad\Rol;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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


    public function consultarPermisos($codigo){
        try{

            $guids = DB::table('perfil_menu as pm')
                ->join('menu as m', 'm.Codigo', '=', 'pm.CodigoMenu')
                ->where('pm.codigoRol', $codigo)
                ->pluck('m.GUID'); // Obtener solo los GUIDs como colección
            return response()->json($guids, 200);

        }catch (\Exception $e) {
            // Capturar otros errores inesperados
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        }
    }

    public function asigarPermisos(Request $request){

        $guids = $request->input('Permisos');
        $perfil = $request->input('Codigo');

        try{

            // 1️⃣ Verificar si el array de GUIDs está vacío (todos los permisos eliminados)
            if (empty($guids)) {
                // 🔥 Eliminar todos los permisos asociados a este códigoRol
                DB::table('perfil_menu')->where('codigoRol', $perfil)->delete();
            
                // 🔥 Invalidar el último token (cerrar sesión forzada)
                DB::table('personal_access_tokens')
                    ->whereIn('tokenable_id', function ($query) use ($perfil) {
                        $query->selectRaw('DISTINCT u.id')
                            ->from('usuario_perfil as up')
                            ->join('users as u', 'u.CodigoPersona', '=', 'up.CodigoPersona')
                            ->where('up.CodigoRol', $perfil);
                    })
                    ->orderByDesc('id')
                    ->limit(1)
                    ->delete();
                
                return response()->json(['message' => 'Todos los permisos han sido eliminados'], 200);
            }
        
            // 2️⃣ Obtener los códigos de menú basados en los GUIDs
            $codigosMenu = DB::table('menu')
                ->whereIn('GUID', $guids)
                ->pluck('Codigo')
                ->toArray();
        
            if (empty($codigosMenu)) {
                return response()->json(['message' => 'No se encontraron menús válidos'], 400);
            }
        
            // 3️⃣ Obtener los códigos actuales en perfil_menu para ese codigoRol
            $codigosActuales = DB::table('perfil_menu')
                ->where('codigoRol', $perfil)
                ->pluck('codigoMenu')
                ->toArray();
        
            // 4️⃣ Determinar qué códigos agregar y cuáles eliminar
            $nuevosCodigos = array_diff($codigosMenu, $codigosActuales);
            $codigosEliminar = array_diff($codigosActuales, $codigosMenu);
        
            // 5️⃣ Insertar nuevos registros si es necesario
            if (!empty($nuevosCodigos)) {
                $nuevosRegistros = array_map(fn($codigoMenu) => [
                    'codigoMenu' => $codigoMenu,
                    'codigoRol' => $perfil
                ], $nuevosCodigos);
            
                DB::table('perfil_menu')->insert($nuevosRegistros);
            }
        
            // 6️⃣ Eliminar registros obsoletos si es necesario
            if (!empty($codigosEliminar)) {
                DB::table('perfil_menu')
                    ->where('codigoRol', $perfil)
                    ->whereIn('codigoMenu', $codigosEliminar)
                    ->delete();
            }

            if(!empty($nuevosCodigos) || !empty($codigosEliminar)){
                DB::table('personal_access_tokens')
                ->whereIn('tokenable_id', function ($query) use ($perfil) {
                    $query->selectRaw('DISTINCT u.id')
                        ->from('usuario_perfil as up')
                        ->join('users as u', 'u.CodigoPersona', '=', 'up.CodigoPersona')
                        ->where('up.CodigoRol', $perfil);
                })
                ->orderByDesc('id')
                ->limit(1)
                ->delete();
            }
                    
            return response()->json(['message' => 'Permisos asignados correctamente'], 200);

        }catch (\Exception $e) {
            // Capturar otros errores inesperados
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.',
                'bd' => $e
            ], 500);
        }
    }
}
