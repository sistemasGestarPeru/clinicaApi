<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\AccesoRequest;
use App\Http\Requests\User\RegistroRequest;
use App\Models\Seguridad\UsuarioPerfil;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class UserController extends Controller
{
    public function listarUsuarios()
    {
        try {
            $usuarios = DB::table('users as u')
                ->join('personas as p', 'p.Codigo', '=', 'u.CodigoPersona')
                ->select([
                    'u.id',
                    'p.Codigo',
                    DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) as NombreCompleto"),
                    'u.name as Usuario',
                    DB::raw("DATE(u.created_at) as FechaCreacion"),
                    'u.Vigente'
                ])
                ->get();

            // Log de éxito
            Log::info('Usuarios listados correctamente', [
                'cantidad' => count($usuarios),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json([
                'res' => true,
                'usuarios' => $usuarios
            ], 200);

        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error inesperado al listar usuarios', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json([
                'res' => false,
                'msg' => 'Error interno'
            ], 500);
        }
    }


    public function consultarUsuario($codigo)
    {
        try {

            $usuarios = User::select('id', 'name', 'Vigente')->where('id', $codigo)->first();

            if (!$usuarios) {

                Log::warning('Usuario no encontrado', [
                    'codigo' => $codigo,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);

                return response()->json([
                    'res' => false,
                    'msg' => 'Usuario no encontrado'
                ], 404);
            }

            // Log de éxito
            Log::info('Usuario Encontrado', [
                'cantidad' => ($usuarios),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($usuarios, 200);

        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error inesperado al consultar usuarios', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json([
                'res' => false,
                'msg' => 'Error interno'
            ], 500);
        }
    }

    public function editarUsuario(Request $request)
    {

        DB::beginTransaction();
        try {

            $user = User::find($request->id);
            $user->name = $request->name;
            $user->Vigente = $request->Vigente;
            $user->save();

            if ($request->Vigente == 0) {
                DB::table('personal_access_tokens')
                    ->where('tokenable_id', $request->id)
                    ->orderByDesc('id')
                    ->limit(1)
                    ->delete();
            }

            DB::commit();

            // Log de éxito
            Log::info('Usuario Editado Correctamente', [
                'Codigo' => $request->id,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);


            return response()->json([
                'res' => true,
                'msg' => 'Usuario Editado Correctamente'
            ], 200);
        } catch (QueryException $e) {
            DB::rollBack();
            // Verificar si el error es por clave duplicada (código SQL 1062)
            if ($e->errorInfo[1] == 1062) {

                Log::warning('El Nombre de Usuario ya existe. Intente con otro nombre.', [
                    'mensaje' => $e->getMessage()
                ]);

                return response()->json([
                    'error' => 'El Nombre de Usuario ya existe. Intente con otro nombre.'
                ], 500);
            }

            Log::error('Ocurrió un error inesperado.', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);


            // Capturar otros errores de SQL
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);

        } catch (\Exception $e) {

            Log::error('Ocurrió un error inesperado.', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            // Capturar otros errores inesperados
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        }
    }

    public function restablecerCredenciales($codigo)
    {

        try {

            $numeroDocumento = DB::table('users as u')
                ->join('personas as p', 'p.Codigo', '=', 'u.CodigoPersona')
                ->where('u.id', $codigo)
                ->value('p.NumeroDocumento');

            $user = User::find($codigo);
            $user->password = bcrypt($numeroDocumento);
            $user->save();

            DB::table('personal_access_tokens')
                ->where('tokenable_id', $codigo)
                ->orderByDesc('id')
                ->limit(1)
                ->delete();

            // Log de éxito
            Log::info('Credenciales restablecidas', [
                'Codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json([
                'res' => true,
                'msg' => 'Credenciales restablecidas'
            ], 200);

        } catch (\Exception $e) {

            Log::error('Ocurrió un error inesperado.', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            // Capturar otros errores inesperados
            return response()->json([
                'res' => false,
                'msg' => 'Error desconocido'
            ], 401);
        }
    }

    public function registro(RegistroRequest $request)
    {
        try {
            $user = new User();
            $user->name = $request->name;
            // $user->email = $request->email;
            $user->password = bcrypt($request->password);
            $user->CodigoPersona = $request->CodigoPersona;
            $user->save();

            // Log de éxito
            Log::info('Usuarios listados correctamente', [
                'Codigo' => $user->id,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json([
                'res' => true,
                'msg' => 'Usuario Registrado Correctamente'
            ], 200);
        } catch (QueryException $e) {
            // Verificar si el error es por clave duplicada (código SQL 1062)
            if ($e->errorInfo[1] == 1062) {

                Log::warning('El Nombre de Usuario ya existe.', [
                    'mensaje' => $e->getMessage()
                ]);

                return response()->json([
                    'error' => 'El Nombre de Usuario ya existe. Intente con otro nombre.'
                ], 500);
            }

            // Capturar otros errores de SQL
            Log::error('Ocurrió un error inesperado.', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        } catch (\Exception $e) {
            // Capturar otros errores inesperados

            Log::error('Ocurrió un error inesperado.', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]); 


            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        }
    }


    public function acceso(AccesoRequest $request)
    {
        try {
            
            $user = User::where('name', $request->identifier)->first();

            if (!$user) {

                Log::warning('Usuario no encontrado', [
                    'Codigo' => $request->identifier
                ]);

                return response()->json([
                    'res' => false,
                    'msg' => 'Usuario no encontrado.'
                ], 401);
            }

            if ($user && !Hash::check($request->password, $user->password)) {

                Log::warning('Credenciales Incorrectas.', [
                    'Codigo' => $request->identifier
                ]);

                return response()->json([
                    'res' => false,
                    'msg' => 'Credenciales Incorrectas.'
                ], 401);
            }


            if ($user->Vigente == 0) {

                Log::warning('Usuario deshabilitado.', [
                    'Codigo' => $request->identifier
                ]);

                return response()->json([
                    'res' => false,
                    'msg' => 'Usuario deshabilitado.'
                ], 403); // Código 403 = Prohibido (usuario no autorizado)
            }

            $trabajador = DB::table('personas as p')
                ->select('p.Nombres', 'p.Apellidos', 'p.Correo')
                ->join('trabajadors as t', 't.Codigo', '=', 'p.Codigo')
                ->where('p.Codigo', '=', $user->CodigoPersona)
                ->first();

            $expiresAt = now()->addHour(5); // Fecha de vencimiento a 10 minutos en el futuro
            $token = $user->createToken($user->name, ['*'], $expiresAt)->plainTextToken;

            $menus = DB::table('perfil_menu as pm')
                ->join('menu as m', 'm.Codigo', '=', 'pm.CodigoMenu')
                ->join('usuario_perfil as up', 'up.CodigoRol', '=', 'pm.CodigoRol')
                ->where('up.CodigoPersona', $user->CodigoPersona)
                ->where('m.Vigente', 1)
                ->orderBy('pm.Codigo')
                ->get(['m.GUID']) // Obtener los GUIDs como un array de objetos
                ->map(function ($item) {
                    return ['GUID' => $item->GUID];
                });

                $aplicaciones = DB::table('usuario_perfil as up')
                ->join('rol as r', 'up.CodigoRol', '=', 'r.Codigo')
                ->join('aplicacion as a', 'r.CodigoAplicacion', '=', 'a.Codigo')
                ->where('up.CodigoPersona', $user->CodigoPersona)
                ->where('r.Vigente', 1)
                ->distinct()
                ->get(['a.Identificador'])
                ->map(function ($item) {
                    return ['Identificador' => $item->Identificador];
                });

            Log::info('Acceso Correcto', [
                'Codigo' => $request->identifier,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);


            $mensaje = 'Acceso Correcto';

            return response()->json([
                'res' => true,
                'token' => $token,
                'userCod' => $user->CodigoPersona,
                'trabajador' => $trabajador,
                'token_expired' => $expiresAt->toIso8601String(),
                'permisos' => $menus,
                'mensaje' => $mensaje,
                'aplicacion' => $aplicaciones
            ], 200);

        } catch (ValidationException $e) {

            Log::warning('Error de validación al logear', [
                'mensaje' => $e->getMessage()
            ]);
            return response()->json([
                'res' => false,
                'msg' => 'Error desconocido'
            ], 401);
        
        } catch (\Exception $e) {
            // Capturar otros errores inesperados

            Log::error('Ocurrió un error inesperado.', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]); 


            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        }
    }

    public function cerrarSesion(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'res' => true,
            'msg' => 'Sesion Cerrada'
        ], 200);
    }


    public function consultarPerfil($codigo, $app)
    {
        try {

            $codApp = DB::table('aplicacion')
            ->where('URL', $app)
            ->value('Codigo');

            if (empty($codigo) || $codigo == 0) {
                return response()->json(['error' => 'La aplicación no existe.'], 404);
            }

            $perfil = DB::table('usuario_perfil as up')
                ->join('rol as r', 'up.CodigoRol', '=', 'r.Codigo')
                ->select('up.Codigo', 'up.CodigoPersona', 'up.CodigoRol')
                ->where('r.CodigoAplicacion', $codApp)
                ->where('up.CodigoPersona', $codigo)
                ->first();

            // Log de éxito
            Log::info('Consulta Perfil', [
                'Codigo' => count($perfil->Codigo),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json([
                'res' => true,
                'perfil' => $perfil
            ], 200);

        } catch (\Exception $e) {
            // Capturar otros errores inesperados

            Log::error('Ocurrió un error inesperado.', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]); 

            return response()->json([
                'res' => false,
                'msg' => 'Error desconocido'
            ], 401);
        }
    }

    public function asginarPerfil(Request $request)
    {
        try {

            $perfil = UsuarioPerfil::find($request->Codigo);

            if ($perfil) {

                $rolAnterior = $perfil->CodigoRol;

                $perfil->update([
                    'CodigoRol' => $request->CodigoRol
                ]);


                if ($rolAnterior != $request->CodigoRol) {
                    DB::table('personal_access_tokens')
                        ->where('tokenable_id', $request->Codigo)
                        ->orderByDesc('id')
                        ->limit(1)
                        ->delete();
                }
            } else {
                $perfil = new UsuarioPerfil();
                $perfil->CodigoPersona = $request->CodigoPersona;
                $perfil->CodigoRol = $request->CodigoRol;
                $perfil->save();
            }

            // Log de éxito
            Log::info('Asignacion Correcta', [
                'Codigo' => ($request->Codigo),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json([
                'res' => true,
                'msg' => 'Rol asignado correctamente'
            ], 200);

        } catch (\Exception $e) {
            // Capturar otros errores inesperados

            Log::error('Ocurrió un error inesperado.', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]); 

            return response()->json([
                'res' => false,
                'msg' => 'Error desconocido'
            ], 401);
        }
    }
}
