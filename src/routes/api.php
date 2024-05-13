<?php

use App\Http\Controllers\API\BlogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\TestimonioController;
use App\Http\Controllers\API\MedicoController;
use App\Http\Controllers\API\PortadaController;
use App\Http\Controllers\API\PromocionController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\SedeController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('registro', [UserController::class, 'registro']);
Route::post('acceso', [UserController::class, 'acceso']);

/* RUTAS PUBLICAS PARA EL INDEX */



Route::get('testimonio/listarIndex', [TestimonioController::class, 'listarUltimos']);
Route::get('testimonio/listarActivos', [TestimonioController::class, 'listarVigente']);
Route::get('medico/ginecologosActivos', [MedicoController::class, 'listarGinecologosVigentes']);
Route::get('medico/biologosActivos', [MedicoController::class, 'listarBiologosVigentes']);
Route::get('blog/listarActivos', [BlogController::class, 'listarVigentes']);

Route::get('blog/consultar/{id}', [BlogController::class, 'consultar']);
Route::post('blog/buscar', [BlogController::class, 'buscarBlog']);

Route::get('promocion/listarActivos', [PromocionController::class, 'listarVigentes']);
Route::get('promocion/consultar/{id}', [PromocionController::class, 'consultar']);


Route::get('portadas/listarActivos/{id}', [PortadaController::class, 'listarVigentes']);

/***************************************************************************/

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::post('cerrarSesion', [UserController::class, 'cerrarSesion']);


    Route::apiResource("sede", SedeController::class);
    Route::apiResource("medico", MedicoController::class);
    Route::apiResource("blog", BlogController::class);
    Route::apiResource("testimonio", TestimonioController::class);
    Route::apiResource("promocion", PromocionController::class);
    Route::apiResource("portadas", PortadaController::class);


    /******************** RUTAS ADICIONALES TESTIMONIO **********************/

    Route::post('testimonio/update', [TestimonioController::class, 'updatePost']);

    /***************************************************************************/

    /*********************** RUTAS ADICIONALES MEDICOS **************************/
    Route::get('listarGinecologos', [MedicoController::class, 'listarGinecologos']);
    Route::get('listarBiologos', [MedicoController::class, 'listarBiologos']);
    Route::post('medico/update', [MedicoController::class, 'updatePost']);

    /***************************************************************************/

    /*********************** RUTAS ADICIONALES Blog **************************/
    Route::post('blog/update', [BlogController::class, 'updatePost']);


    /***************************************************************************/

    /*********************** RUTAS ADICIONALES PROMOCION **************************/

    Route::post('promocion/update', [PromocionController::class, 'updatePost']);

    /*********************** RUTAS ADICIONALES PORTADAS **************************/
    Route::post('portadas/update', [PortadaController::class, 'updatePost']);
    Route::get('portadas/consultar/{id}', [PortadaController::class, 'consultarListado']);

    /***************************************************************************/
});
