<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\AccesoRequest;
use App\Http\Requests\User\RegistroRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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
        $user = User::where('email', $request->identifier)
            ->orWhere('name', $request->identifier)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'msg' => ['Las credenciales son incorrectas!'],
            ]);
        }
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
