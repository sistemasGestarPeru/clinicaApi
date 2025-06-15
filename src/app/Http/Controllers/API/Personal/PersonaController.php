<?php

namespace App\Http\Controllers\API\Personal;

use App\Http\Controllers\Controller;
use App\Models\Personal\Persona;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PersonaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Persona::all();
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

    public function consultarPerfil($codigo)
    {

        try {

            $persona = DB::table('personas as p')
                ->join('tipo_documentos as td', 'p.CodigoTipoDocumento', '=', 'td.Codigo')
                ->where('p.Codigo', $codigo)
                ->select(
                    'p.Codigo',
                    'p.Nombres',
                    'p.Apellidos',
                    'p.Direccion',
                    'p.Celular',
                    'p.Correo',
                    'td.Siglas',
                    'p.NumeroDocumento'
                )
                ->first(); // O usar ->get() si esperas múltiples resultados
            //log info
            Log::info('Consultar Perfil', [
                'Controlador' => 'PersonaController',
                'Metodo' => 'consultarPerfil',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'codigo_persona' => $codigo
            ]);

            return response()->json($persona);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar perfil', [
                'Controlador' => 'PersonaController',
                'Metodo' => 'consultarPerfil',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'codigo_persona' => $codigo
            ]);
            // Capturar otros errores inesperados
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        }
    }


    public function actualizarPerfil(Request $request)
    {
        try {
            // 1️⃣ Validar los datos antes de actualizar
            $validator = Validator::make($request->all(), [
                'Codigo'   => 'required|exists:personas,Codigo',
                'Direccion' => 'required|string|max:255',
                'Celular'  => [
                    'required',
                    'string',
                    'size:9', // Asegura que tenga exactamente 9 caracteres
                    'regex:/^9\d{8}$/', // Debe empezar con "9" y tener 8 dígitos más
                ],
                'Correo'   => 'required|email|max:255',
            ]);

            if ($validator->fails()) {

                //log warning
                Log::warning('Errores de validación al actualizar perfil', [
                    'Controlador' => 'PersonaController',
                    'Metodo' => 'actualizarPerfil',
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                    'detalles' => $validator->errors()
                ]);

                return response()->json([
                    'error' => 'Errores de validación',
                    'detalles' => $validator->errors()
                ], 422);
            }

            // 2️⃣ Buscar la persona y actualizar los datos
            $persona = Persona::findOrFail($request->Codigo);
            $persona->Direccion = $request->Direccion;
            $persona->Celular = $request->Celular;
            $persona->Correo = $request->Correo;
            $persona->save();

            //log info
            Log::info('Perfil actualizado correctamente', [
                'Controlador' => 'PersonaController',
                'Metodo' => 'actualizarPerfil',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'codigo_persona' => $request->Codigo
            ]);

            return response()->json('Perfil actualizado correctamente', 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al actualizar perfil', [
                'Controlador' => 'PersonaController',
                'Metodo' => 'actualizarPerfil',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'command' => $request->all()
            ]);

            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        }
    }

    public function cambiarContrasenia(Request $request)
    {
        try {
            // 1️⃣ Validación de los datos recibidos
            $validator = Validator::make($request->all(), [
                'usuario' => 'required|exists:users,CodigoPersona', // Asegura que el usuario existe
                'dni' => 'required|string', // DNI es obligatorio
                'actual' => 'required|string',
                'nueva' => [
                    'required',
                    'string',
                    'min:8',
                    'max:15',
                    'regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@*+&])[A-Za-z\d@*+&$]+$/', // Letras, números y especial
                    function ($attribute, $value, $fail) use ($request) {
                        if ($value === $request->dni) {
                            $fail('La nueva contraseña no puede ser igual al Numero de Documento.');
                        }
                    }
                ]
            ]);

            if ($validator->fails()) {
                //log warning
                Log::warning('Errores de validación al cambiar contraseña', [
                    'Controlador' => 'PersonaController',
                    'Metodo' => 'cambiarContrasenia',
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                    'detalles' => $validator->errors()
                ]);
                return response()->json([
                    'error' => 'Errores de validación',
                    'detalles' => $validator->errors()
                ], 422);
            }

            // 2️⃣ Buscar el usuario con Eloquent
            $user = User::where('CodigoPersona', $request->usuario)->first();

            // 3️⃣ Verificar la contraseña actual
            if (!Hash::check($request->actual, $user->password)) {

                //log warning
                Log::warning('Contraseña actual incorrecta al cambiar contraseña', [
                    'Controlador' => 'PersonaController',
                    'Metodo' => 'cambiarContrasenia',
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                    'codigo_usuario' => $request->usuario
                ]);

                return response()->json([
                    'error' => 'La contraseña actual no es correcta.'
                ], 400);
            }

            // 4️⃣ Actualizar la contraseña
            $user->password = bcrypt($request->nueva);
            $user->save();


            //log info
            Log::info('Contraseña actualizada correctamente', [
                'Controlador' => 'PersonaController',
                'Metodo' => 'cambiarContrasenia',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'codigo_usuario' => $request->usuario
            ]);

            return response()->json(['message' => 'Contraseña actualizada correctamente.'], 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al cambiar contraseña', [
                'Controlador' => 'PersonaController',
                'Metodo' => 'cambiarContrasenia',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'command' => $request->all()
            ]);

            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.',
                'bd' => $e->getMessage()
            ], 500);
        }
    }
}
