<?php

use App\Http\Controllers\API\BlogController;
use App\Http\Controllers\API\Caja\CajaController;
use App\Http\Controllers\API\CategoriaProducto\CategoriaProductoController;
use App\Http\Controllers\API\Cliente\ClienteController;
use App\Http\Controllers\API\Compra\CompraController;
use App\Http\Controllers\API\Consultas\ConsultasTrabajadorController;
use App\Http\Controllers\API\ContratoProducto\ContratoProductoeController;
use App\Http\Controllers\API\ControladoresGenerales\ControladorGeneralController;
use App\Http\Controllers\API\Detraccion\DetraccionController;
use App\Http\Controllers\API\LocalDocumentoVenta\LocalDocumentoVentaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\TestimonioController;
use App\Http\Controllers\API\MedicoController;
use App\Http\Controllers\API\MotivoNotaCredito\MotivoNotaCreditoController;
use App\Http\Controllers\API\Pago\PagoController;
use App\Http\Controllers\API\PagoComision\PagoComisionController;
use App\Http\Controllers\API\PagoDonante\PagoDonanteController;
use App\Http\Controllers\API\PagoServicio\PagoProveedorController;
use App\Http\Controllers\API\PagoServicio\PagoServicioController;
use App\Http\Controllers\API\PagosVarios\PagosVariosController;
use App\Http\Controllers\API\PagoTrabajadores\PagoTrabajadoresController;
use App\Http\Controllers\API\Personal\AsignacionSedeController;
use App\Http\Controllers\API\Personal\ContratoLaboralController;
use App\Http\Controllers\API\Personal\DepartamentoController;
use App\Http\Controllers\API\Personal\EmpresaController;
use App\Http\Controllers\API\Personal\NacionalidadController;
use App\Http\Controllers\API\Personal\SedeController as PersonalSedeController;
use App\Http\Controllers\API\Personal\TipoDocumentoController;
use App\Http\Controllers\API\Personal\TrabajadorController;
use App\Http\Controllers\API\PortadaController;
use App\Http\Controllers\API\Producto\ProductoController;
use App\Http\Controllers\API\PromocionController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\SedeController;
use App\Http\Controllers\API\Venta\VentaController;
use App\Http\Controllers\API\SedeProducto\SedeProductoController;
use App\Http\Controllers\API\TipoDocumentoVenta\TipoDocumentoVentaController;

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
Route::post('/consultaApp', [UserController::class, 'verificarAplicacion']);

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
Route::get('promocion/listarSedes', [PortadaController::class, 'listarSedes']);






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

    /***************************************************************************/
    /********************************* CONSULTA SIDEBAR *********************************/
    Route::get('/consultaTrab/empresa/{codigoPersona}', [ConsultasTrabajadorController::class, 'ConsultaEmpresasTrab']);
    Route::get('/consultaTrab/sedes/{codigoPersona}/{codigoEmpresa}', [ConsultasTrabajadorController::class, 'ConsultaSedesTrab']);

    /********************************* COMBOS *********************************/

    Route::get('combos/listarTipoDocIdentidad', [ControladorGeneralController::class, 'listarTipoDocIdentidad']);
    Route::get('combos/listarTiposDocVenta/{sede}/{tipo}', [ControladorGeneralController::class, 'listarTiposDocVenta']);
    Route::get('combos/empresas', [ControladorGeneralController::class, 'listarEmpresas']);
    Route::get('combos/sedes/{codigoEmpresa}', [ControladorGeneralController::class, 'listarSedesEmpresas']);
    Route::get('combos/sedesDisponibles/{codigoEmpresa}/{codigoTrabajador}', [ControladorGeneralController::class, 'cboSedesDisponibles']); //Cambiar si se usa de manera general
    Route::get('combos/empresasDisponibles/{codigoTrabajador}', [ControladorGeneralController::class, 'cboEmpresasDisponibles']); //Cambiar si se usa de manera general
    Route::get('combos/departamentoSede/{sede}', [ControladorGeneralController::class, 'listarDepartamentos']); //Cambiar si se usa de manera general
    Route::get('combos/empresasTrabajador/{codigoTrabajador}', [ControladorGeneralController::class, 'ConsultaEmpresasTrab']);
    Route::get('combos/listarMedioPago/{sede}', [ControladorGeneralController::class, 'listarMedioPago']);
    Route::get('combos/listarCuentasBancariasEmpresa/{empresa}', [ControladorGeneralController::class, 'listarCuentasBancariasEmpresa']);
    Route::get('combos/listarBilleterasDigitalesEmpresa/{empresa}', [ControladorGeneralController::class, 'listarBilleterasDigitalesEmpresa']);
    Route::get('combos/listarMotivosAnulacion', [ControladorGeneralController::class, 'listarMotivosAnulacion']);
    Route::get('combos/cuentaDetraccion/{empresa}', [ControladorGeneralController::class, 'cuentaDetraccion']);
    Route::get('combos/listarSistemaPension', [ControladorGeneralController::class, 'listarSistemaPension']);
    Route::get('combos/listarMotivoPagoServicio', [ControladorGeneralController::class, 'listarMotivoPagoServicio']);
    Route::get('combos/personalAutorizado/{sede}', [ControladorGeneralController::class, 'personalAutorizado']);
    Route::get('combos/personal/{sede}', [ControladorGeneralController::class, 'personal']);
    Route::get('combos/listarTipoMoneda', [ControladorGeneralController::class, 'listarTipoMoneda']);
    Route::get('combos/listarMedicos/{sede}', [ControladorGeneralController::class, 'listarMedicos']);
    Route::get('combos/listarPacientes/{sede}', [ControladorGeneralController::class, 'listarPacientes']);
    Route::get('combos/listarMotivoNotaCredito', [ControladorGeneralController::class, 'listarMotivoNotaCredito']);
    Route::get('combos/listarMotivoAnulacionContrato', [ControladorGeneralController::class, 'listarMotivoAnulacionContrato']);
    Route::get('combos/listarDonantes', [ControladorGeneralController::class, 'listarDonantes']);
    Route::get('combos/listarMotivoAnulacionContrato', [ControladorGeneralController::class, 'listarMotivoAnulacionContrato']);
    Route::get('combos/listarCategoriaProducto', [ControladorGeneralController::class, 'listarCategoriaProducto']);


    
    /***************************************************************************/
    /********************************* CLIENTES *********************************/
    Route::apiResource("cliente", ClienteController::class);
    Route::post('cliente/registrarCliente', [ClienteController::class, 'registrarCliente']);
    Route::post('cliente/registrarClienteEmpresa', [ClienteController::class, 'registrarClienteEmpresa']);
    Route::post('cliente/busca', [ClienteController::class, 'listaCliente']);
    Route::post('cliente/consulta', [ClienteController::class, 'consultaCliente']);
    Route::post('cliente/consultaDatosCliente', [ClienteController::class, 'consultaDatosCliente']);
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
    // Route::post('trabajador/consultarContrato', [TrabajadorController::class, 'consultarContratoLab']); //Corregir
    Route::get('trabajador/listarContratos/{codTrab}', [TrabajadorController::class, 'listarContratos']);
    Route::get('trabajador/consultarContratoLab/{codContratoLab}', [TrabajadorController::class, 'consultarContrato']);
    Route::get('trabajador/listarAsignaciones/{codTrab}/{codEmpresa}', [TrabajadorController::class, 'listarAsignaciones']);
    Route::get('trabajador/consultarAsignacion/{codAsignacion}', [TrabajadorController::class, 'consultarAsignacion']);
    Route::post('trabajador/actualizarContratoLaboral', [TrabajadorController::class, 'actualizarContrato']);
    Route::post('trabajador/actualizarAsignacionSede', [TrabajadorController::class, 'actualizarAsignacion']);
    /***************************************************************************/
    /***************************** CONTRATO ******************************/
    Route::get('contratoProducto/visualizarContrato/{contrato}', [ContratoProductoeController::class, 'visualizarContrato']);
    Route::post('contratoProducto/buscarProducto', [ContratoProductoeController::class, 'buscarProducto']);
    Route::post('contratoProducto/registrarContratoProducto', [ContratoProductoeController::class, 'registrarContratoProducto']);
    Route::post('contratoProducto/buscarContratoProducto', [ContratoProductoeController::class, 'buscarContratoProducto']);
    Route::post('contratoProducto/anularContrato', [ContratoProductoeController::class, 'anularContrato']);
    Route::get('contratoProducto/historialContratoVenta/{codigo}', [ContratoProductoeController::class, 'historialContratoVenta']);
    Route::get('contratoProducto/contratoPDF/{contrato}', [ContratoProductoeController::class, 'contratoPDF']);
    /********************************* CAJA *********************************/
    Route::apiResource("caja", CajaController::class);
    Route::post('caja/estadoCajaLogin', [CajaController::class, 'estadoCajaLogin']);
    Route::post('caja/cerrarCaja', [CajaController::class, 'cerrarCaja']);
    Route::get('caja/consultarDatosCaja/{caja}', [CajaController::class, 'consultarDatosCaja']);
    Route::get('caja/datosCajaExcel/{caja}', [CajaController::class, 'datosCajaExcel']);
    Route::post('caja/consultarEstadoCaja', [CajaController::class, 'consultarEstadoCaja']);
    Route::post('caja/registrarIngreso', [CajaController::class, 'registrarIngreso']);
    Route::post('caja/registrarSalida', [CajaController::class, 'registrarSalida']);    
    Route::post('caja/ingresosPendientes', [CajaController::class, 'listarIngresosPendientes']);
    Route::get('caja/listarTrabajadores/{sede}', [CajaController::class, 'listarTrabajadoresSalidaDinero']);
    /********************************* VENTA *********************************/
    Route::get('venta/consultarNotaCreditoVenta/{venta}', [VentaController::class, 'consultarNotaCreditoVenta']);
    Route::post('venta/consultarDocumentoVenta', [VentaController::class, 'consultarDocumentoVenta']);
    Route::post('venta/consultarNotaCredito', [VentaController::class, 'consultarNotaCredito']);
    Route::post('venta/registrarVenta', [VentaController::class, 'registrarVenta']);
    Route::post('venta/registrarNotaCredito', [VentaController::class, 'registrarNotaCredito']);
    Route::post('venta/serieCanje', [VentaController::class, 'serieCanje']);
    Route::post('venta/consultaNumDocumentoVenta', [VentaController::class, 'consultaNumDocumentoVenta']);
    Route::post('venta/buscarCliente', [VentaController::class, 'buscarCliente']);
    Route::post('venta/buscarVenta', [VentaController::class, 'buscarVenta']);
    Route::post('venta/consultarDatosContratoProducto', [VentaController::class, 'consultarDatosContratoProducto']);


    
    Route::post('venta/anularVenta', [VentaController::class, 'anularVenta']);
    Route::post('venta/consultarVenta', [VentaController::class, 'consultarVenta']);
    Route::post('venta/canjearDocumentoVenta', [VentaController::class, 'canjearDocumentoVenta']);
    Route::post('venta/registrarPagoVenta', [VentaController::class, 'registrarPagoVenta']);
    Route::post('venta/consultarSerie', [VentaController::class, 'consultarSerie']);
    Route::post('venta/consultarSerieNotaCredito', [VentaController::class, 'consultarSerieNotaCredito']);
    Route::post('venta/consultarTipoProducto', [VentaController::class, 'consultarTipoProducto']);
    Route::post('venta/buscarProductos', [VentaController::class, 'buscarProductos']);
    Route::get('venta/cuentaDetraccion/{empresa}', [VentaController::class, 'cuentaDetraccion']);
    Route::get('venta/boletaVentaPDF/{venta}', [VentaController::class, 'boletaVentaPDF']);
    Route::get('venta/facturaVentaPDF/{venta}', [VentaController::class, 'facturaVentaPDF']);
    Route::get('venta/notaCreditoPDF/{venta}', [VentaController::class, 'notaCreditoPDF']);
    Route::get('venta/notaVentaPDF/{venta}', [VentaController::class, 'notaVentaPDF']);
    Route::get('venta/listarPagosAsociados/{venta}', [VentaController::class, 'listarPagosAsociados']);
    Route::get('venta/anularPago/{venta}/{pago}', [VentaController::class, 'anularPago']);
    /********************************* PAGOS *********************************/
    Route::post('pago/registrarPago', [PagoController::class, 'registrarPago']);
    Route::post('pago/buscarPago', [PagoController::class, 'buscarPago']);
    Route::post('pago/buscarVentas', [PagoController::class, 'buscarVentas']);
    Route::post('pago/registrarPagoDocumentoVenta', [PagoController::class, 'registrarPagoDocumentoVenta']);
    Route::post('pago/anularPago', [PagoController::class, 'anularPago']);
    Route::post('pago/consultarPago', [PagoController::class, 'consultarPago']);
    Route::post('pago/editarPago', [PagoController::class, 'editarPago']);

    /********************************** PAGO TRABAJADORES / PLANILLA  **********************************/
    Route::post('pagoTrabajadores/listarPagosRealizados', [PagoTrabajadoresController::class, 'listarPagosRealizados']);
    Route::post('pagoTrabajadores/listarTrabajadoresPlanilla', [PagoTrabajadoresController::class, 'listarTrabajadoresPlanilla']);
    Route::post('pagoTrabajadores/buscarTrabajador', [PagoTrabajadoresController::class, 'buscarTrabajadorPago']);
    Route::post('pagoTrabajadores/registrarPlanilla', [PagoTrabajadoresController::class, 'registrarPlanilla']);
    Route::post('pagoTrabajadores/registrarPagoIndividual', [PagoTrabajadoresController::class, 'registrarPagoIndividual']);

    /********************************* Compras *********************************/
    Route::post('compra/listarProveedor', [CompraController::class, 'listarProveedor']);
    Route::post('compra/listarProducto', [CompraController::class, 'listarProducto']);
    Route::post('compra/registrarCompra', [CompraController::class, 'registrarCompra']);
    Route::post('compra/listarCompras', [CompraController::class, 'listarCompras']);
    Route::get('compra/consultarCompra/{codigo}', [CompraController::class, 'consultarCompra']);
    Route::post('compra/listarPagosAdelantados', [CompraController::class, 'listarPagosAdelantados']);

    /********************************** PAGO PROVEEDOR **********************************/

    Route::post('pagoProveedor/registrarPago', [PagoProveedorController::class, 'registrarPago']);

    Route::post('pagoProveedor/listarComprasProveedores', [PagoProveedorController::class, 'listarComprasProveedores']);
    Route::post('pagoProveedor/listarCuotasProveedor', [PagoProveedorController::class, 'listarCuotasProveedor']);

    /********************************** PAGO DONANTE **********************************/
    Route::post('pagoDonante/registrarPagoDonante', [PagoDonanteController::class, 'registrarPagoDonante']);
    Route::post('pagoDonante/listarPagosDonante', [PagoDonanteController::class, 'listarPagosDonante']);
    Route::get('pagoDonante/consultarPagoDonante/{codigo}', [PagoDonanteController::class, 'consultarPagoDonante']);
    /********************************** PAGO SERVICIOS **********************************/
    Route::post('pagoServicio/registrarPago', [PagoServicioController::class, 'registrarPago']);
    Route::post('pagoServicio/listarPagos', [PagoServicioController::class, 'listarPagos']);
    Route::get('pagoServicio/consultarPagoServicio/{codigo}', [PagoServicioController::class, 'consultarPagoServicio']);
    
    /********************************** PAGO COMISION **********************************/
    Route::post('pagoComision/registrarPagoComision', [PagoComisionController::class, 'registrarPagoComision']);
    Route::post('pagoComision/listarPagosComisiones', [PagoComisionController::class, 'listarPagosComisiones']);
    Route::post('pagoComision/listarDocumentos', [PagoComisionController::class, 'listarDocumentos']);
    Route::get('pagoComision/consultarPagoComision/{codigo}', [PagoComisionController::class, 'consultarPagoComision']);
    /********************************** PAGOS VARIOS **********************************/
    Route::post('pagosVarios/registrarPagoVarios', [PagosVariosController::class, 'registrarPagoVarios']);
    Route::post('pagosVarios/listarPagosVarios', [PagosVariosController::class, 'listarPagosVarios']);
    Route::get('pagosVarios/consultarPagosVarios/{codigo}', [PagosVariosController::class, 'consultarPagosVarios']);
    
    /********************************** PRODUCTO **********************************/
    Route::post('producto/registrarProducto', [ProductoController::class, 'registrarProducto']);
    Route::post('producto/listarProducto', [ProductoController::class, 'listarProducto']);

    /********************************** SEDE PRODUCTO **********************************/
    Route::get('sedeProducto/listarSedeProducto/{sede}', [SedeProductoController::class, 'listarSedeProducto']);
    Route::get('sedeProducto/listarProductosNoAsignados', [SedeProductoController::class, 'listarProductosNoAsignados']);
    Route::post('sedeProducto/registrarProductoSede', [SedeProductoController::class, 'registrarProductoSede']);

/********************************** CATEGORIA PRODUCTO **********************************/
    Route::get('categoriaProducto/listarCategoriaProducto', [CategoriaProductoController::class, 'listarCategoriaProducto']);

/********************************** TIPOS DOCUMENTOS IDENTIDAD **********************************/

    Route::get('tipoDocIdentidad/listarTipoDocumentos', [TipoDocumentoController::class, 'listarTipoDocumentos']);
    Route::post('tipoDocIdentidad/registrarTipoDocumento', [TipoDocumentoController::class, 'registrarTipoDocumento']);

    /********************************** MOTIVOS NOTA DE CREDITO **********************************/
    Route::get('motivosNotaCredito/listarMotivos', [MotivoNotaCreditoController::class, 'listarMotivos']);
    Route::post('motivosNotaCredito/registrarMotivos', [MotivoNotaCreditoController::class, 'registrarMotivos']);

/********************************** DETRACCION **********************************/
    Route::get('detraccion/listarDetraccion/{sede}', [DetraccionController::class, 'listarDetraccionesPendientes']);

    /********************************** TIPOS DOCUMENTOS VENTA **********************************/
    Route::get('tiposDocVenta/listarTipoDocumentoVenta', [TipoDocumentoVentaController::class, 'listarTipoDocumentoVenta']);
    Route::post('tiposDocVenta/registrarDocVenta', [TipoDocumentoVentaController::class, 'registrarDocVenta']);

    /********************************** SEDE DOCUMENTO VENTA **********************************/
    Route::get('sedeDocVenta/listarSedeDocumentoVenta/{sede}', [LocalDocumentoVentaController::class, 'listarSedeDocumentoVenta']);
    Route::post('sedeDocVenta/registrarSedeDocVenta', [LocalDocumentoVentaController::class, 'registrarSedeDocVenta']);

    /********************************* PRUEBAS *********************************/
    Route::get('nacionalidad/listar', [NacionalidadController::class, 'index']);
    Route::get('departamento/listar', [DepartamentoController::class, 'index']);
    Route::get('asignacionsede/listar', [AsignacionSedeController::class, 'index']);
    Route::get('sedeEmpresa/listar', [PersonalSedeController::class, 'index']);
    Route::get('empresa/listar', [EmpresaController::class, 'index']);
    Route::get('contratolaboral/listar', [ContratoLaboralController::class, 'index']);

    /***************************************************************************/
});
