<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\AccesoRequest;
use App\Http\Requests\User\RegistroRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function registro(RegistroRequest $request)
    {
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->CodigoPersona = $request->CodigoPersona;
        $user->save();
        return response()->json([
            'res' => true,
            'msg' => 'Usuario Registrado Correctamente'
        ], 200);
    }

    

    public function acceso(AccesoRequest $request)
    {
        try{
            $user = User::where('email', $request->identifier)
            ->orWhere('name', $request->identifier)
            ->first();
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'res' => false,
                    'msg' => 'Credenciales incorrectas'
                ], 401);
            }

            $trabajador = DB::table('personas as p')
            ->select('p.Nombres', 'p.Apellidos', 'p.Correo')
            ->join('trabajadors as t', 't.Codigo', '=', 'p.Codigo')
            ->where('p.Codigo', '=', $user->CodigoPersona)
            ->first();

            $expiresAt = now()->addHour(2); // Fecha de vencimiento a 10 minutos en el futuro
            $token = $user->createToken($user->email, ['*'], $expiresAt)->plainTextToken;

            $mensaje = 'Acceso Correcto';
            return response()->json([
                'res' => true,
                'token' => $token,
                'userCod' => $user->CodigoPersona,
                'trabajador' => $trabajador,
                'token_expired' => $expiresAt->toIso8601String(),
                'mensaje' => $mensaje
            ], 200);
        }catch(ValidationException $e){
            return response()->json([
                'res' => false,
                'msg' => 'Error desconocido'
            ], 401);
        }
    }

    public function verificarAplicacion(Request $request)
    {
        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d H:i:s');

        $query = $request->input('query');

        // Separar el ID del token y el token plano
        $tokenParts = explode('|', $query['token']);
        if (count($tokenParts) !== 2) {
            return response()->json(
                [
                    'rpta' => false,
                    'msg' => 'Token inválido'
                ],
                200
            );
        }

        $tokenId = $tokenParts[0]; // ID del token
        $plainTextToken = $tokenParts[1]; // Token plano

        try {
            // Verificar permisos
            $existePermiso = DB::table('Usuario_Perfil')
                ->where('Vigente', 1)
                ->where('CodigoPersona', $query['user'])
                ->where('CodigoAplicacion', $query['id'])
                ->exists();

            if (!$existePermiso) {
                return response()->json(
                    [
                        'rpta' => false,
                        'msg' => 'No tiene permisos para acceder a esta aplicación'
                    ],
                    200
                );
            }

            // Buscar token en la base de datos
            $tokenValido = DB::table('personal_access_tokens')
                ->where('id', $tokenId)
                ->where('expires_at', '>', $fecha)
                ->select('token')
                ->first();

            if (!$tokenValido || !hash_equals($tokenValido->token, hash('sha256', $plainTextToken))) {
                return response()->json(
                    [
                        'rpta' => false,
                        'msg' => 'Sesión inválida o expirada'
                    ],
                    200
                );
            }

            // Respuesta en caso de éxito
            return response()->json(
                [
                    'rpta' => true,
                    'msg' => 'Acceso válido'
                ],
                200
            );
        } catch (\Exception $e) {
            return response()->json(
                [
                    'rpta' => false,
                    'msg' => 'Error desconocido'
                ],
                401
            );
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

}
