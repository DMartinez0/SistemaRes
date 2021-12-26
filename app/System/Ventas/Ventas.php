<?php
namespace App\System\Ventas;


use App\Models\TicketOrden;
use App\Models\Producto;
use App\Models\TicketProducto;
use App\Common\Helpers;
use App\Models\Cliente;
use App\Models\OpcionesProducto;
use App\Models\TicketDelivery;
use App\Models\TicketNum;
use App\Models\TicketOpcion;

trait Ventas{


    public function agregarOrden(){
        $orden = TicketOrden::create([
            'clientes' => session('clientes'), // Numero de clientes
            'tipo_servicio' => session('config_tipo_servicio'),
            'empleado' => session('config_usuario_id'), //usuario
            'llevar_aqui' => session('llevar_aqui'),
            'nombre_mesa' => session('mesa_nombre_temp'),
            'comentario' => null,
            'edo' => 1,
            'usuario_borrado' => null,
            'motivo_borrado' => null,
            'imprimir' => 1,
            'clave' => Helpers::hashId(),
            'tiempo' => Helpers::timeId(),
            'td' => config('sistema.td')
        ]);
    
        session(['orden' => $orden->id]);
        session()->forget('mesa_nombre_temp');


        if (session('client_select')) {
            $this->addDeliveryData();
            $this->getDeliveryData();
            session()->forget('client_select');
         }

    }



    public function agregarProducto($cod){

    $producto = Producto::where('cod', $cod)->first();

    $stotal = Helpers::STotal($producto->precio, session('config_impuesto'));
    $impuesto = Helpers::Impuesto($stotal, session('config_impuesto'));
    
    $creado = TicketProducto::create([
        'cod' => $cod, 
        'cantidad' => 1, 
        'producto' => $producto->nombre,
        'pv' => $producto->precio, 
        'stotal' => $stotal,
        'imp' => $impuesto,
        'total' => $producto->precio,
        'descuento' => null,
        'num_fact' => null,
        'orden' => session('orden'),
        'cliente' => session('cliente'),
        'cancela' => null,
        'tipo_pago' => 1,
        'usuario' => session('config_usuario_id'),
        'cajero' => null,
        'tipo_venta' => 1,
        'gravado' => $producto->gravado,
        'edo' => 1,
        'panel' => $producto->panel,
        'imprimir' => 1, // 1 = agregado 2 = listo a imprimir, 0 impreso
        'clave' => Helpers::hashId(),
        'tiempo' => Helpers::timeId(),
        'td' => config('sistema.td')
    ]); 

    if (session('principal_ticket_pantalla') == 1) { // pasa a 1 el estado de impresion para la pantalla
        $this->estadoImprimirOrden(session('orden'), 1);
    }

    if($producto->opciones_active == 1){
        // agregar las opciones del producto
        $this->agregarOpciones($producto->id, $creado->id);
        return $this->levantarModalOpcion($creado->id); // busca y levanta el modal de la opcion que falta
    } else {
        return FALSE;
    }
}



// Agregan opciones al producto
public function agregarOpciones($idProducto, $productoAgregado){ // agraga las opcions asignadas al producto

    $opciones = OpcionesProducto::where('producto_id', $idProducto)->get();
    foreach ($opciones as $opcion) {
        TicketOpcion::create([
            'opcion_primaria' => $opcion->opcion_id, 
            'opcion_producto_id' => NULL, 
            'ticket_producto_id' => $productoAgregado, 
            'clave' => Helpers::hashId(),
            'tiempo' => Helpers::timeId(),
            'td' => config('sistema.td')
        ]); 
    }
}





public function totalDeVenta(){
    return TicketProducto::where('orden', session('orden'))
                                        ->where('num_fact', NULL)
                                        ->sum('total');
}

public function getProductosAgregados(){
    return TicketProducto::where('orden', session('orden'))
                                ->where('num_fact', NULL)
                                ->where('edo', 1)
                                ->with('subOpcion')->get();
}

public function guardarProductosImprimir(){
    TicketProducto::where('orden', session('orden'))
                    ->where('edo', 1)
                    ->where('imprimir', 1)
                    ->update(['imprimir' => 2]);
}

public function cantidaProductosSinGuardar(){
    if (session('principal_ticket_pantalla') == 2) { /// imprimir en 1 y 4 guardados y eliminados
            return TicketProducto::where('orden', session('orden'))
                                    ->where('num_fact', NULL)
                                    ->whereIn('imprimir', [1, 4])
                                    ->count();
    } else { /// si es pantalla solo en eliminados
            return TicketProducto::where('orden', session('orden'))
                                    ->where('num_fact', NULL)
                                    ->where('imprimir', 1)
                                    ->count();
    }
}


public function getProductosModal($producto){ // obtiene los detalles de los productos par ael modal
    return TicketProducto::where('orden', session('orden'))
                            ->where('num_fact', NULL)
                            ->where('cod', $producto)
                            ->with('subOpcion')->get();
}


public function getUltimaFacturaNumber(){
    return TicketNum::select('factura')
                        ->where('tipo_venta', session('impresion_seleccionado'))
                        ->orderBy('factura', 'desc')
                        ->first();
}


public function actualizarDatosVenta($num_fact){ // actualiza los campos de los productos al vender
    TicketProducto::where('orden', session('orden'))
                    ->where('cliente', session('cliente'))
                    ->update(['num_fact' => $num_fact, 
                            'cancela' => session('cliente'), 
                            'cajero' => session('config_usuario_id'),
                            'tipo_pago' => session('tipo_pago'),
                            'tipo_venta' => session('impresion_seleccionado')
    ]);
}



public function eliminarProducto($iden){
    if (session('principal_ticket_pantalla')) {
        $cantidad = TicketProducto::where('orden', session('orden'))
                                    ->where('imprimir', 1)->count();
        if ($cantidad > 0) {
            TicketProducto::destroy($iden);
        } else {
            TicketProducto::where('id', $iden)
                            ->update(['edo' => 2, 'imprimir' => 4]);
        }
    } else {
        TicketProducto::destroy($iden);
    }
}

public function eliminarOrden(){
    if (session('principal_ticket_pantalla')) {
        $cantidad = TicketProducto::where('orden', session('orden'))
                                    ->where('imprimir', 3)->count();
        if ($cantidad > 0) {
            TicketProducto::where('orden', session('orden'))
            ->where('imprimir', 3)
            ->update(['edo' => 2, 'imprimir' => 4]);
            TicketProducto::where('orden', session('orden'))->where('imprimir','!=', 4)->delete();
            TicketOrden::where('id', session('orden'))
            ->update(['edo' => 0, 'imprimir' => 0]);
        } else {
            TicketOrden::destroy(session('orden'));
            TicketProducto::where('orden', session('orden'))->delete();
        }
    } else {
        TicketOrden::destroy(session('orden'));
        TicketProducto::where('orden', session('orden'))->delete();
    }
}







public function levantarModalOpcion($idProducto){ // busca y levanta el modal de la opcion que falta
    
    $opcion = TicketOpcion::where('ticket_producto_id', $idProducto)
                            ->where('opcion_producto_id', NULL)->first();
    
    if ($opcion) {
        return [
            'opcion_id' => $opcion->opcion_primaria,
            'producto_id' => $idProducto
        ];
    } else {
        return FALSE;
    }
    
}


public function compruebaVentaRapida(){ // Comprueba orden sin completar en venta rapida de este usuario para iniciar
    
    $opcion = TicketOrden::where('tipo_servicio', 1)
                         ->where('empleado', session('config_usuario_id'))
                         ->where('edo', 1)
                         ->first();
    if ($opcion) {
        return $opcion;
    } else {
        return FALSE;
    }
}



public function addDeliveryData(){
        TicketDelivery::create([
        'orden_id' => session('orden'), 
        'cliente_id' => session('client_select'),
        'repartidor_id' => session('repartidor_select'),
        'ingreso' => now(), 

        'clave' => Helpers::hashId(),
        'tiempo' => Helpers::timeId(),
        'td' => config('sistema.td')
    ]);

}



public function getDeliveryData(){ // crea la variables del delivery

    $cliente = Cliente::where('id', session('client_select'))
                         ->first();

            session(['delivery_nombre' => $cliente->nombre]);
            session(['delivery_direccion' => $cliente->direccion]);
            session(['delivery_telefono' => $cliente->telefono]);
    }

public function delDeliveryData(){ 
    
    session()->forget('delivery_nombre');
    session()->forget('delivery_direccion');
    session()->forget('delivery_telefono');

    }
    

    // cuota de envi
    public function cuotaEnvio(){
        $cantidad = TicketProducto::where('orden', session('orden'))
                                    ->where('cod', 9999)
                                    ->count();

        if ($cantidad == 0) {
            $this->agregarCuotaEnvio();
        }
    }

    public function agregarCuotaEnvio(){

        $stotal = Helpers::STotal(session('config_envio'), session('config_impuesto'));
        $impuesto = Helpers::Impuesto($stotal, session('config_impuesto'));
        
        $creado = TicketProducto::create([
            'cod' => 9999, 
            'cantidad' => 1, 
            'producto' => "Delivery",
            'pv' => session('config_envio'), 
            'stotal' => $stotal,
            'imp' => $impuesto,
            'total' => session('config_envio'),
            'descuento' => null,
            'num_fact' => null,
            'orden' => session('orden'),
            'cliente' => session('cliente'),
            'cancela' => null,
            'tipo_pago' => 1,
            'usuario' => session('config_usuario_id'),
            'cajero' => null,
            'tipo_venta' => 1,
            'gravado' => 1,
            'edo' => 1,
            'panel' => NULL,
            'imprimir' => 0, // 1 = agregado 2 = listo a imprimir, 0 impreso
            'clave' => Helpers::hashId(),
            'tiempo' => Helpers::timeId(),
            'td' => config('sistema.td')
        ]); 
    }
/// funciones de ordenes o mesas


    public function updateImprimirOrden(){ /// actualiza el estado de imprimir en la orden
        $cantidad = TicketProducto::where('orden', session('orden'))
                                    ->whereIn('imprimir', [2, 4])->count();

        if ($cantidad > 0) {
            $this->estadoImprimirOrden(session('orden'), 1);
        } else {
            $this->estadoImprimirOrden(session('orden'), 0);
        }
    }

    public function estadoImprimirOrden($orden, $estado){
        TicketOrden::where('id', $orden)
                          ->update(['imprimir' => $estado, 'tiempo' => Helpers::timeId()]);
    }

    public function updateLLevarAquiOrden(){
        TicketOrden::where('id', session('orden'))
                    ->update(['llevar_aqui' => session('llevar_aqui')]);
    }



    /// retorna la cantidad de productos en la orden 
    public function getCantidadProductosCod($cod){
        return TicketProducto::where('orden', session('orden'))
                                ->where('cod', $cod)
                                ->where('num_fact', NULL)
                                ->whereIn('imprimir', [1, 2, 3])
                                ->count();
    }

    public function reducirCantidadCod($cant, $cod){
    
            TicketProducto::where('orden', session('orden'))
                            ->where('cod', $cod)
                            ->where('num_fact', NULL)
                            ->whereIn('imprimir', [1, 2, 3])
                            ->limit($cant)
                            ->update(['edo' => 2, 'imprimir' => 4]);

    }

    public function aumentarCantidadCod($cant, $cod){

        for ($i=0; $i < $cant; $i++) { 
            $this->agregarProducto($cod);
        }
    }




    
    public function addOtrasVentas($producto, $cantidad){

    
        $stotal = Helpers::STotal($cantidad, session('config_impuesto'));
        $impuesto = Helpers::Impuesto($stotal, session('config_impuesto'));
        
        TicketProducto::create([
            'cod' => 99999, 
            'cantidad' => 1, 
            'producto' => $producto,
            'pv' => $cantidad, 
            'stotal' => $stotal,
            'imp' => $impuesto,
            'total' => $cantidad,
            'descuento' => null,
            'num_fact' => null,
            'orden' => session('orden'),
            'cliente' => session('cliente'),
            'cancela' => null,
            'tipo_pago' => 1,
            'usuario' => session('config_usuario_id'),
            'cajero' => null,
            'tipo_venta' => 1,
            'gravado' => 1,
            'edo' => 1,
            'panel' => NULL,
            'imprimir' => 1, // 1 = agregado 2 = listo a imprimir, 0 impreso
            'clave' => Helpers::hashId(),
            'tiempo' => Helpers::timeId(),
            'td' => config('sistema.td')
        ]); 
    
        if (session('principal_ticket_pantalla') == 1) { // pasa a 1 el estado de impresion para la pantalla
            $this->estadoImprimirOrden(session('orden'), 1);
        }
    
            return FALSE;
        
    }




}