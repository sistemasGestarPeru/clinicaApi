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
        try {

            $user = User::join('Usuario_Perfil as up', 'up.CodigoPersona', '=', 'users.CodigoPersona')
                ->where(function ($query) use ($request) {
                    $query->where('users.email', $request->identifier)
                        ->orWhere('users.name', $request->identifier);
                })
                ->where('users.Vigente', 1)
                ->where('up.Vigente', 1)
                ->where('up.CodigoAplicacion', $request->id)
                ->select('users.id', 'users.CodigoPersona', 'users.email', 'users.password')
                ->first();

            if (!$user) {
                return response()->json([
                    'res' => false,
                    'msg' => 'Usuario no encontrado'
                ], 401);
            }

            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'res' => false,
                    'msg' => 'Credenciales Incorrectas'
                ], 401);
            }

            // $aplicaciones = DB::table('Usuario_Perfil')
            //     ->where('Vigente', 1)
            //     ->where('CodigoPersona', $user->CodigoPersona)
            //     ->select('CodigoAplicacion as app')
            //     ->get();

            $expiresAt = now()->addHour(2); // Fecha de vencimiento a 10 minutos en el futuro
            $token = $user->createToken($user->email, ['*'], $expiresAt)->plainTextToken;

            $mensaje = 'Acceso Correcto';
            return response()->json([
                'res' => true,
                'token' => $token,
                'userCod' => $user->CodigoPersona,
                'token_expired' => $expiresAt->toIso8601String(),
                'mensaje' => $mensaje
            ], 200);
        } catch (ValidationException $e) {
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

    public function getUserDetails($codigoPersona)
    {
        // Obtener los detalles del usuario
        $userDetails = User::where('CodigoPersona', $codigoPersona)
            ->with(['persona.trabajador'])
            ->first(['id', 'CodigoPersona']);

        if (!$userDetails) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $response = [
            'id' => $userDetails->id,
            'CorreoCorporativo' => $userDetails->persona->trabajador->CorreoCorporativo,
            'Nombres' => $userDetails->persona->Nombres,
            'Apellidos' => $userDetails->persona->Apellidos,
        ];

        return response()->json($response);
    }
}
