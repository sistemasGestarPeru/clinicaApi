<?php

namespace App\Http\Controllers\API\Rol;

use App\Http\Controllers\Controller;
use App\Models\Seguridad\Rol;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    public function listarRolesVigentes($app)
    {
        try {

            $codigo = DB::table('aplicacion')
                ->where('URL', $app)
                ->value('Codigo');

            if (empty($codigo) || $codigo == 0) {

                //log warning
                Log::warning('Aplicación no encontrada', [
                    'Controlador' => 'RolController',
                    'Método' => 'listarRolesVigentes',
                    'Aplicación' => $app
                ]);

                return response()->json(['error' => 'La aplicación no existe.'], 404);
            }

            $roles = Rol::where('Vigente', 1)->where('CodigoAplicacion', $codigo)->get();

            //log info
            Log::info('Listar Roles Vigentes', [
                'Controlador' => 'RolController',
                'Método' => 'listarRolesVigentes',
                'Cantidad' => $roles->count(),
                'Aplicación' => $app,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($roles, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al listar roles vigentes', [
                'Controlador' => 'RolController',
                'Método' => 'listarRolesVigentes',
                'Aplicación' => $app,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            // Capturar otros errores inesperados
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        }
    }

    public function listarRoles($app)
    {

        try {
            $codigo = DB::table('aplicacion')
                ->where('URL', $app)
                ->value('Codigo');

            if (empty($codigo) || $codigo == 0) {

                //log warning
                Log::warning('Aplicación no encontrada', [
                    'Controlador' => 'RolController',
                    'Método' => 'listarRoles',
                    'Aplicación' => $app
                ]);

                return response()->json(['error' => 'La aplicación no existe.'], 404);
            }

            $roles = Rol::select('Codigo', 'Nombre', 'Vigente')->where('CodigoAplicacion', $codigo)->get();

            //log info
            Log::info('Listar Roles', [
                'Controlador' => 'RolController',
                'Método' => 'listarRoles',
                'Cantidad' => $roles->count(),
                'Aplicación' => $app,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($roles, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al listar roles', [
                'Controlador' => 'RolController',
                'Método' => 'listarRoles',
                'Aplicación' => $app,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            // Capturar otros errores inesperados
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        }
    }

    public function consultarRol($codigo)
    {
        try {

            $rol = Rol::findOrFail($codigo);

            //log info
            Log::info('Consultar Rol', [
                'Controlador' => 'RolController',
                'Método' => 'consultarRol',
                'Codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($rol, 200);
        } catch (\Exception $e) {
            // Capturar otros errores inesperados
            Log::error('Error al consultar Rol', [
                'Controlador' => 'RolController',
                'Método' => 'consultarRol',
                'Codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        }
    }


    public function registroRol(Request $request)
    {
        try {

            $codigo = DB::table('aplicacion')
                ->where('URL', $request->aplicacion)
                ->value('Codigo');

            if (empty($codigo) || $codigo == 0) {

                //log warning
                Log::warning('Aplicación no encontrada', [
                    'Controlador' => 'RolController',
                    'Método' => 'registroRol',
                    'Aplicación' => $request->aplicacion
                ]);

                return response()->json(['error' => 'La aplicación no existe.'], 404);
            }

            $request->merge(['CodigoAplicacion' => $codigo]);

            Rol::create($request->all());

            //log info
            Log::info('Registrar Rol', [
                'Controlador' => 'RolController',
                'Método' => 'registroRol',
                'Nombre' => $request->Nombre,
                'Comando' => $request->all(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['message' => 'Rol registrado correctamente.'], 201);
        } catch (QueryException $e) {
            // Verificar si el error es por clave duplicada (código SQL 1062)
            if ($e->errorInfo[1] == 1062) {

                //log warning
                Log::warning('Error al registrar Rol', [
                    'Controlador' => 'RolController',
                    'Método' => 'registroRol',
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                    'mensaje' => $e->getMessage(),
                    'linea' => $e->getLine(),
                    'archivo' => $e->getFile(),
                    'Comando' => $request->all()
                ]);

                return response()->json([
                    'error' => 'El Nombre del Rol ya existe. Intente con otro nombre.'
                ], 500);
            }

            //log error
            Log::error('Error al registrar Rol', [
                'Controlador' => 'RolController',
                'Método' => 'registroRol',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'Comando' => $request->all()
            ]);

            // Capturar otros errores de SQL
            return response()->json([
                'error' => 'Error en la base de datos. Inténtelo nuevamente.'
            ], 500);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al registrar Rol', [
                'Controlador' => 'RolController',
                'Método' => 'registroRol',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'Comando' => $request->all()
            ]);

            // Capturar otros errores inesperados
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        }
    }

    public function actualizarRol(Request $request)
    {
        try {

            $rol = Rol::findOrFail($request->Codigo);
            $rol->update($request->all());

            //log info
            Log::info('Actualizar Rol', [
                'Controlador' => 'RolController',
                'Método' => 'actualizarRol',
                'Codigo' => $request->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['message' => 'Rol actualizado correctamente'], 200);
        } catch (QueryException $e) {
            // Verificar si el error es por clave duplicada (código SQL 1062)
            if ($e->errorInfo[1] == 1062) {

                //log warning
                Log::warning('Error al actualizar Rol', [
                    'Controlador' => 'RolController',
                    'Método' => 'actualizarRol',
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                    'mensaje' => $e->getMessage(),
                    'linea' => $e->getLine(),
                    'archivo' => $e->getFile(),
                    'Comando' => $request->all()
                ]);

                return response()->json([
                    'error' => 'El Nombre del Rol ya existe. Intente con otro nombre.'
                ], 500);
            }

            //log error
            Log::error('Error al actualizar Rol', [
                'Controlador' => 'RolController',
                'Método' => 'actualizarRol',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'Comando' => $request->all()
            ]);

            // Capturar otros errores de SQL
            return response()->json([
                'error' => 'Error en la base de datos. Inténtelo nuevamente.'
            ], 500);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al actualizar Rol', [
                'Controlador' => 'RolController',
                'Método' => 'actualizarRol',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'Comando' => $request->all()
            ]);

            // Capturar otros errores inesperados
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        }
    }


    public function consultarPermisos($codigo)
    {
        try {

            $guids = DB::table('perfil_menu as pm')
                ->join('menu as m', 'm.Codigo', '=', 'pm.CodigoMenu')
                ->where('pm.codigoRol', $codigo)
                ->pluck('m.GUID'); // Obtener solo los GUIDs como colección

            //log info
            Log::info('Consultar Permisos', [
                'Controlador' => 'RolController',
                'Método' => 'consultarPermisos',
                'CodigoRol' => $codigo,
                'CantidadPermisos' => $guids->count(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($guids, 200);
        } catch (\Exception $e) {
            // Capturar otros errores inesperados
            Log::error('Error al consultar Permisos', [
                'Controlador' => 'RolController',
                'Método' => 'consultarPermisos',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'Codigo' => $codigo
            ]);

            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        }
    }

    public function asigarPermisos(Request $request)
    {

        $guids = $request->input('Permisos');
        $perfil = $request->input('Codigo');

        try {

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


                //log info
                Log::info('Eliminar todos los permisos', [
                    'Controlador' => 'RolController',
                    'Método' => 'asigarPermisos',
                    'Comando' => $request->all(),
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);

                return response()->json(['message' => 'Todos los permisos han sido eliminados'], 200);
            }

            // 2️⃣ Obtener los códigos de menú basados en los GUIDs
            $codigosMenu = DB::table('menu')
                ->whereIn('GUID', $guids)
                ->pluck('Codigo')
                ->toArray();

            if (empty($codigosMenu)) {

                //log warning
                Log::warning('No se encontraron menús válidos', [
                    'Controlador' => 'RolController',
                    'Método' => 'asigarPermisos',
                    'Comando' => $request->all(),
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);

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

            if (!empty($nuevosCodigos) || !empty($codigosEliminar)) {
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

            //log info
            Log::info('Asignar Permisos', [
                'Controlador' => 'RolController',
                'Método' => 'asigarPermisos',
                'Comando' => $request->all(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['message' => 'Permisos asignados correctamente'], 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al asignar permisos', [
                'Controlador' => 'RolController',
                'Método' => 'asigarPermisos',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'Comando' => $request->all()
            ]);

            // Capturar otros errores inesperados
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.',
                'bd' => $e
            ], 500);
        }
    }
}
