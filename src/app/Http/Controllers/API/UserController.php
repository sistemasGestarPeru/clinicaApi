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

class UserController extends Controller
{

    public function listarUsuarios(){
        try{

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

            return response()->json([
                'res' => true,
                'usuarios' => $usuarios
            ], 200);
        }catch(ValidationException $e){
            return response()->json([
                'res' => false,
                'msg' => 'Error desconocido'
            ], 401);
        }
    }

    public function consultarUsuario($codigo){
        try{

            $usuarios = User::select('id', 'name', 'Vigente')->where('id', $codigo)->first();

            return response()->json($usuarios, 200);
        }catch(ValidationException $e){
            return response()->json([
                'res' => false,
                'msg' => 'Error desconocido'
            ], 401);
        }
    }

    public function editarUsuario(Request $request){

        DB::beginTransaction();
        try{

            $user = User::find($request->id);
            $user->name = $request->name;
            $user->Vigente = $request->Vigente;
            $user->save();


            DB::table('personal_access_tokens')
            ->where('tokenable_id', $request->id)
            ->orderByDesc('id')
            ->limit(1)
            ->delete();
            DB::commit();
            return response()->json([
                'res' => true,
                'msg' => 'Usuario Editado Correctamente'
            ], 200);

        }catch (QueryException $e) {
            DB::rollBack();
            // Verificar si el error es por clave duplicada (código SQL 1062)
            if ($e->errorInfo[1] == 1062) {
                return response()->json([
                    'error' => 'El Nombre de Usuario ya existe. Intente con otro nombre.'
                ], 500);
            }
    
            // Capturar otros errores de SQL
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
            
        } catch (\Exception $e) {
            // Capturar otros errores inesperados
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        }
    }

    public function restablecerCredenciales(Request $request){
        try{
            $user = User::find($request->Codigo);
            $user->password = bcrypt($request->dni);
            $user->save();
            return response()->json([
                'res' => true,
                'msg' => 'Credenciales restablecidas'
            ], 200);
        }catch(ValidationException $e){
            return response()->json([
                'res' => false,
                'msg' => 'Error desconocido'
            ], 401);
        }
    }

    public function registro(RegistroRequest $request)
    {
        try{
            $user = new User();
            $user->name = $request->name;
            // $user->email = $request->email;
            $user->password = bcrypt($request->password);
            $user->CodigoPersona = $request->CodigoPersona;
            $user->save();
            return response()->json([
                'res' => true,
                'msg' => 'Usuario Registrado Correctamente'
            ], 200);
        }catch (QueryException $e) {
            // Verificar si el error es por clave duplicada (código SQL 1062)
            if ($e->errorInfo[1] == 1062) {
                return response()->json([
                    'error' => 'El Nombre de Usuario ya existe. Intente con otro nombre.'
                ], 500);
            }
    
            // Capturar otros errores de SQL
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
            
        } catch (\Exception $e) {
            // Capturar otros errores inesperados
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        }
    }

    
    public function acceso(AccesoRequest $request)
    {
        try{
            $user = User::where('name', $request->identifier)->first();

            if (!$user) {
                return response()->json([
                    'res' => false,
                    'msg' => 'Credenciales incorrectas'
                ], 401);
            }
            
            if ($user->Vigente == 0) {
                return response()->json([
                    'res' => false,
                    'msg' => 'Usuario deshabilitado'
                ], 403); // Código 403 = Prohibido (usuario no autorizado)
            }

            $trabajador = DB::table('personas as p')
            ->select('p.Nombres', 'p.Apellidos', 'p.Correo')
            ->join('trabajadors as t', 't.Codigo', '=', 'p.Codigo')
            ->where('p.Codigo', '=', $user->CodigoPersona)
            ->first();

            $expiresAt = now()->addHour(2); // Fecha de vencimiento a 10 minutos en el futuro
            $token = $user->createToken($user->name, ['*'], $expiresAt)->plainTextToken;

            // $menus = DB::table('menu')->select('GUID')->where('vigente', 1)->get();
            

            $menus = DB::table('perfil_menu as pm')
            ->join('menu as m', 'm.Codigo', '=', 'pm.CodigoMenu')
            ->join('usuario_perfil as up', 'up.CodigoRol', '=', 'pm.CodigoRol')
            ->where('up.CodigoPersona', $user->CodigoPersona)
            ->orderBy('pm.Codigo')
            ->get(['m.GUID']) // Obtener los GUIDs como un array de objetos
            ->map(function ($item) {
                return ['GUID' => $item->GUID];
            });
        


            $mensaje = 'Acceso Correcto';
            return response()->json([
                'res' => true,
                'token' => $token,
                'userCod' => $user->CodigoPersona,
                'trabajador' => $trabajador,
                'token_expired' => $expiresAt->toIso8601String(),
                'permisos' => $menus,
                'mensaje' => $mensaje
            ], 200);
        }catch(ValidationException $e){
            return response()->json([
                'res' => false,
                'msg' => 'Error desconocido'
            ], 401);
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


    public function consultarPerfil($codigo){
        try{
            
            $perfil = UsuarioPerfil::where('CodigoPersona', $codigo)->first();

            return response()->json([
                'res' => true,
                'perfil' => $perfil
            ], 200);

        }catch(ValidationException $e){
            return response()->json([
                'res' => false,
                'msg' => 'Error desconocido'
            ], 401);
        }
    }

    public function asginarPerfil(Request $request){
        try{

            $perfil = UsuarioPerfil::find($request->Codigo);

            if($perfil){
                $perfil->update([
                    'CodigoRol' => $request->CodigoRol
                ]);
            }else{
                $perfil = new UsuarioPerfil();
                $perfil->CodigoPersona = $request->CodigoPersona;
                $perfil->CodigoRol = $request->CodigoRol;
                $perfil->save();
            }

            return response()->json([
                'res' => true,
                'msg' => 'Rol asignado correctamente'
            ], 200);

        }catch(ValidationException $e){
            return response()->json([
                'res' => false,
                'msg' => 'Error desconocido'
            ], 401);
        }
    }

}
