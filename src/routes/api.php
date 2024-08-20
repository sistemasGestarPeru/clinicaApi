<?php

use App\Http\Controllers\API\BlogController;
use App\Http\Controllers\API\Caja\CajaController;
use App\Http\Controllers\API\Cliente\ClienteController;
use App\Http\Controllers\API\Consultas\ConsultasTrabajadorController;
use App\Http\Controllers\API\ContratoProducto\ContratoProductoeController;
use App\Http\Controllers\API\ControladoresGenerales\ControladorGeneralController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\TestimonioController;
use App\Http\Controllers\API\MedicoController;
use App\Http\Controllers\API\Personal\AsignacionSedeController;
use App\Http\Controllers\API\Personal\ContratoLaboralController;
use App\Http\Controllers\API\Personal\DepartamentoController;
use App\Http\Controllers\API\Personal\EmpresaController;
use App\Http\Controllers\API\Personal\NacionalidadController;
use App\Http\Controllers\API\Personal\PersonaController;
use App\Http\Controllers\API\Personal\SedeController as PersonalSedeController;
use App\Http\Controllers\API\Personal\TipoDocumentoController;
use App\Http\Controllers\API\Personal\TrabajadorController;
use App\Http\Controllers\API\PortadaController;
use App\Http\Controllers\API\PromocionController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\SedeController;
use App\Http\Controllers\API\Venta\VentaController;

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

/* RUTAS PUBLICAS PARA EL INDEX DE LA PAG WEB*/

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
/********************************* CONSULTA SIDEBAR *********************************/
Route::get('/detallesUsuario/{codigoPersona}', [UserController::class, 'getUserDetails']); //detalles del usuario
Route::get('/consultaTrab/empresa/{codigoPersona}', [ConsultasTrabajadorController::class, 'ConsultaEmpresasTrab']);
Route::get('/consultaTrab/sedes/{codigoPersona}/{codigoEmpresa}', [ConsultasTrabajadorController::class, 'ConsultaSedesTrab']);

/********************************* COMBOS *********************************/
Route::get('combos/listarTiposDocVenta', [ControladorGeneralController::class, 'listarTiposDocVenta']);
Route::get('combos/empresas', [ControladorGeneralController::class, 'listarEmpresas']);
Route::get('combos/sedes/{codigoEmpresa}', [ControladorGeneralController::class, 'listarSedesEmpresas']);
Route::get('combos/sedesDisponibles/{codigoEmpresa}/{codigoTrabajador}', [ControladorGeneralController::class, 'cboSedesDisponibles']); //Cambiar si se usa de manera general
Route::get('combos/empresasDisponibles/{codigoTrabajador}', [ControladorGeneralController::class, 'cboEmpresasDisponibles']); //Cambiar si se usa de manera general
Route::get('combos/departamentoSede/{sede}', [ControladorGeneralController::class, 'listarDepartamentos']); //Cambiar si se usa de manera general
Route::get('combos/empresasTrabajador/{codigoTrabajador}', [ControladorGeneralController::class, 'ConsultaEmpresasTrab']);
/***************************************************************************/
/********************************* CLIENTES *********************************/
Route::apiResource("cliente", ClienteController::class);
Route::post('cliente/registrarCliente', [ClienteController::class, 'registrarCliente']);
Route::post('cliente/registrarClienteEmpresa', [ClienteController::class, 'registrarClienteEmpresa']);
Route::post('cliente/busca', [ClienteController::class, 'listaCliente']);
Route::post('cliente/consulta', [ClienteController::class, 'consultaCliente']);
Route::post('cliente/actualizarCliente', [ClienteController::class, 'actualizarCliente']);
Route::post('cliente/actualizarClienteEmpresa', [ClienteController::class, 'actualizarClienteEmpresa']);
/***************************************************************************/

/********************************* Trabajador *********************************/
Route::post('trabajador/registrarTrabajador', [TrabajadorController::class, 'registrarTrabajador']);
Route::post('trabajador/registrarPersonaTrabajador', [TrabajadorController::class, 'registrarPersonaTrabajador']);
Route::post('trabajador/actualizarTrabajador', [TrabajadorController::class, 'actualizarTrabajador']);
Route::post('trabajador/buscar', [TrabajadorController::class, 'buscar']);
Route::post('trabajador/consultar', [TrabajadorController::class, 'consultarTrabCodigo']);
Route::post('trabajador/consultarNumDoc', [TrabajadorController::class, 'consultarNumDoc']);
Route::post('trabajador/registrarContratoLaboral', [TrabajadorController::class, 'regContratoLab']);
Route::post('trabajador/registrarAsignacionSede', [TrabajadorController::class, 'regAsignacionSede']);
Route::post('trabajador/consultarContrato', [TrabajadorController::class, 'consultarContratoLab']); //Corregir
Route::get('trabajador/listarContratos/{codTrab}', [TrabajadorController::class, 'listarContratos']);
Route::get('trabajador/consultarContratoLab/{codContratoLab}', [TrabajadorController::class, 'consultarContrato']);
Route::get('trabajador/listarAsignaciones/{codTrab}/{codEmpresa}', [TrabajadorController::class, 'listarAsignaciones']);
Route::get('trabajador/consultarAsignacion/{codAsignacion}', [TrabajadorController::class, 'consultarAsignacion']);
Route::post('trabajador/actualizarContratoLaboral', [TrabajadorController::class, 'actualizarContrato']);
Route::post('trabajador/actualizarAsignacionSede', [TrabajadorController::class, 'actualizarAsignacion']);
/***************************************************************************/
/***************************** CONTRATO ******************************/

Route::post('contratoProducto/buscarProducto', [ContratoProductoeController::class, 'buscarProducto']);
Route::post('contratoProducto/registrarContratoProducto', [ContratoProductoeController::class, 'registrarContratoProducto']);
Route::post('contratoProducto/buscarContratoProducto', [ContratoProductoeController::class, 'buscarContratoProducto']);


/********************************* CAJA *********************************/
Route::apiResource("caja", CajaController::class);
Route::post('caja/cerrarCaja', [CajaController::class, 'cerrarCaja']);
Route::post('caja/consultarCaja', [CajaController::class, 'consultarCaja']);
Route::post('caja/consultarEstadoCaja', [CajaController::class, 'consultarEstadoCaja']);

/********************************* VENTA *********************************/
Route::post('venta/consultarDatosContratoProducto', [VentaController::class, 'consultarDatosContratoProducto']);
Route::post('venta/registrarVenta', [VentaController::class, 'registrarVenta']);
Route::post('venta/buscarCliente', [VentaController::class, 'buscarCliente']);
Route::post('venta/buscarVenta', [VentaController::class, 'buscarVenta']);

/********************************* PRUEBAS *********************************/
Route::get('nacionalidad/listar', [NacionalidadController::class, 'index']);
Route::get('tipodocumento/listar', [TipoDocumentoController::class, 'index']);
Route::get('departamento/listar', [DepartamentoController::class, 'index']);
Route::get('asignacionsede/listar', [AsignacionSedeController::class, 'index']);
Route::get('sedeEmpresa/listar', [PersonalSedeController::class, 'index']);
Route::get('empresa/listar', [EmpresaController::class, 'index']);
Route::get('contratolaboral/listar', [ContratoLaboralController::class, 'index']);
/***************************************************************************/
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
