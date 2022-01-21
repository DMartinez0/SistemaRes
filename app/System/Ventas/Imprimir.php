<?php
namespace App\System\Ventas;

use App\Common\Helpers;
use App\Models\ConfigApp;
use App\Models\TicketNum;
use App\Models\TicketOrden;
use App\Models\TicketProducto;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

/*
Los tipos de impresion se distribuiran asi:
1. Pre Cuenta
2. Comanda (produsctos guardados)
3. Factura (Ya cancelada, independiente si es factura, tickeet, ccf. etc)
4. Comanda (Productos Eliminados)

*/

trait Imprimir{


    public function ImprimirFactura($factura){ // para factura
      
        $datos = $this->getTotalFactura($factura);
        $datos['productos'] = $this->getProductosFactura($factura);
        $datos['empresa'] = $this->getDatosEmpresa();
        $datos['caja'] = session('caja_select');
        $datos['documento_factura'] = session('impresion_seleccionado');
        $datos['no_factura'] = $factura;
        $datos['fecha'] = date('d-m-Y');
        $datos['hora'] = date('H:i:s');
        $datos['tipo_moneda'] = Helpers::paisSimbolo(session('config_pais'));
        // $datos['cliente'] = $this->getDatosCliente();
        $datos['cajero'] = Auth::user()->name;
        $datos['config_imp'] = session('config_impuesto');
        $datos['tipo_impresion'] = 3;
        $datos['identidad'] = config('sistema.td');
        $datos['numero_documento'] = $factura; // numero de factura
        $datos['llevar_aqui'] = session('llevar_aqui'); // llevar o comer aqui

        Http::asForm()->post('http://'.config('sistema.ip').'/impresiones/index.php', $datos);

        // Http::asForm()->post('http://localhost/impresiones/index.php', ['datos' => $datos]);
    }



    public function ImprimirPrecuenta($cliente = NULL, $propina, $porcentaje){ // si trae cliente o no 
        
        if ($cliente) {
            $datos = $this->getTotalOrdenCliente(session('orden'), $cliente, $propina, $porcentaje);
            $datos['productos'] = $this->getProductosOrdenCliente(session('orden'), $cliente);
        } else {
            $datos = $this->getTotalOrden(session('orden'), $propina, $porcentaje);
            $datos['productos'] = $this->getProductosOrden(session('orden'));
        }

        $datos['empresa'] = $this->getDatosEmpresa();
        $datos['caja'] = session('caja_select');
        $datos['documento_factura'] = session('impresion_seleccionado');
        $datos['fecha'] = date('d-m-Y');
        $datos['hora'] = date('H:i:s');
        $datos['tipo_moneda'] = Helpers::paisSimbolo(session('config_pais'));
        // $datos['cliente'] = $this->getDatosCliente();
        $datos['cajero'] = Auth::user()->name;
        $datos['config_imp'] = session('config_impuesto');
        $datos['tipo_impresion'] = 1;
        $datos['identidad'] = config('sistema.td');
        $datos['numero_documento'] = session('orden'); // numero de orden
        $datos['llevar_aqui'] = session('llevar_aqui'); // llevar o comer aqui

        $datos['cliente_nombre'] = session('delivery_nombre'); 
        $datos['cliente_direccion'] = session('delivery_direccion'); 
        $datos['cliente_telefono'] = session('delivery_telefono'); 
        $datos['mesa'] = $this->detallesMesa(session('orden'));

        Http::asForm()->post('http://'.config('sistema.ip').'/impresiones/index.php', $datos);
    }


    public function ImprimirComanda(){
        if ($this->contarProductos(2) > 0) {
            if ($this->contarProductosPanel(2, 1) > 0) {
                $this->productosComanda(2, 1);
            }
            if ($this->contarProductosPanel(2, 2) > 0) {
                $this->productosComanda(2, 2);
            }
            if ($this->contarProductosPanel(2, 3) > 0) {
                $this->productosComanda(2, 3);
            }
        }
        if ($this->contarProductos(4) > 0) {
            if ($this->contarProductosPanel(4, 1) > 0) {
                $this->productosComanda(4, 1);
            }
            if ($this->contarProductosPanel(4, 2) > 0) {
                $this->productosComanda(4, 2);
            }
            if ($this->contarProductosPanel(4, 3) > 0) {
                $this->productosComanda(4, 3);
            }
        }
    }



    public function AbrirCaja(){
      
        $datos['caja'] = session('caja_select');
        $datos['cajero'] = Auth::user()->name;
        $datos['tipo_impresion'] = 5;
        $datos['identidad'] = config('sistema.td');

        Http::asForm()->post('http://'.config('sistema.ip').'/impresiones/index.php', $datos);

        // Http::asForm()->post('http://localhost/impresiones/index.php', ['datos' => $datos]);
    }



    public function productosComanda($imprimir, $panel){
       // imprimir: 2 - guardados, 4 Eliminados
        $datos['productos'] = $this->getProductosComanda(session('orden'), $imprimir, $panel);
        $datos['cajero'] = Auth::user()->name;
        $datos['tipo_impresion'] = $imprimir;
        $datos['panel'] = $panel;
        $datos['fecha'] = date('d-m-Y');
        $datos['hora'] = date('H:i:s');
        $datos['identidad'] = config('sistema.td');
        $datos['numero_documento'] = session('orden'); // numero de orden
        $datos['llevar_aqui'] = session('llevar_aqui'); // llevar o comer aqui

        $datos['cliente_nombre'] = session('delivery_nombre'); 
        $datos['cliente_direccion'] = session('delivery_direccion'); 
        $datos['cliente_telefono'] = session('delivery_telefono'); 
        $datos['mesa'] = $this->detallesMesa(session('orden'));

        Http::asForm()->post('http://'.config('sistema.ip').'/impresiones/index.php', $datos);

        $this->productosActualizar(session('orden'), $imprimir, 3, $panel); // (orden,imprimir,tipo de impresion, panel)

    }



    public function getDatosEmpresa(){
        $conf = ConfigApp::find(1);
        $datos['empresa_nombre'] = $conf->cliente;
        $datos['empresa_slogan'] = $conf->slogan;
        $datos['empresa_direccion'] = $conf->direccion;
        $datos['empresa_telefono'] = $conf->telefono;
        $datos['empresa_email'] = $conf->email;
        $datos['empresa_propietario'] = $conf->propietario;
        $datos['empresa_giro'] = $conf->giro;
        $datos['empresa_nit'] = $conf->nit;
        return $datos;
    }

    
    public function getDatosCliente(){

    }

    private function formatData($datos){
        $datos = $datos->sortBy('cod');
        $datos->values()->all();
        $count = 0;
        $conteo = 0;
        $data = [];
        foreach ($datos as $producto) {     
            if ($count != $producto->cod) {

            $cod = $datos->where('cod', $producto->cod);
            $cod->all();
            $cant = count($cod);
            $total = $cod->sum('total');
            $count = $producto->cod;
                $data[$conteo]['cant'] = $cant;
                $data[$conteo]['producto'] = $producto->producto;        
                $data[$conteo]['pv'] = $producto->pv;
                $data[$conteo]['imp'] = $producto->imp;
                $data[$conteo]['total'] = $total;
            $conteo ++;  
            }
        }

        return $data;
    }

    private function formatDataComanda($datos){
        $conteo = 0;
        $data = [];
        foreach ($datos as $producto) {   
                $data[$conteo]['subOpcion'] = [];  

                $data[$conteo]['cant'] = $producto->cantidad;
                $data[$conteo]['producto'] = $producto->producto;  
                $x = 0;  
                foreach ($producto->subOpcion as $opcion) {
                    $data[$conteo]['subOpcion'][$x]['nombre'] = $opcion->nombre;
                $x ++;
                }    
            $conteo ++;  
        }

        return $data;
    }

    public function getProductosOrden($orden){
        $datos =  TicketProducto::where('orden', $orden)
        ->where('num_fact', NULL)
        ->with('subOpcion')->get();

        return $this->formatData($datos);
    }


    public function getProductosOrdenCliente($orden, $cliente){
        $datos =  TicketProducto::where('orden', $orden)
        ->where('cliente', $cliente)
        ->with('subOpcion')->get();

        return $this->formatData($datos);
    }


    public function getProductosFactura($factura){
        $datos =  TicketProducto::where('num_fact', $factura)
        ->where('tipo_venta', session('impresion_seleccionado'))
        ->with('subOpcion')->get();

        return $this->formatData($datos);
    }


    public function getTotalOrden($orden, $propina, $porcentaje){

        $datos = array();
        $datos['subtotal'] = TicketProducto::where('orden', $orden)
                            ->where('num_fact', NULL)
                            ->sum('stotal');
        $datos['impuestos'] = TicketProducto::where('orden', $orden)
                            ->where('num_fact', NULL)
                            ->sum('imp');
        $datos['total'] = TicketProducto::where('orden', $orden)
                            ->where('num_fact', NULL)
                            ->sum('total');

        $datos['propina_cant'] = $propina;
        $datos['propina_porcent'] = $porcentaje;

        return $datos;
    }    



    public function getTotalOrdenCliente($orden, $cliente, $propina, $porcentaje){

        $datos = array();
        $datos['subtotal'] = TicketProducto::where('orden', $orden)
                            ->where('num_fact', NULL)
                            ->where('cliente', $cliente)
                            ->sum('stotal');
        $datos['impuestos'] = TicketProducto::where('orden', $orden)
                            ->where('num_fact', NULL)
                            ->where('cliente', $cliente)
                            ->sum('imp');
        $datos['total'] = TicketProducto::where('orden', $orden)
                            ->where('num_fact', NULL)
                            ->where('cliente', $cliente)
                            ->sum('total');
        
        $datos['propina_cant'] = $propina;
        $datos['propina_porcent'] = $porcentaje;

        return $datos;  
    }    


    public function getTotalFactura($factura){
        $datos = array();
        $datos['subtotal'] = TicketProducto::where('num_fact', $factura)
                            ->where('tipo_venta', session('impresion_seleccionado'))
                            ->sum('stotal');
        $datos['impuestos'] = TicketProducto::where('num_fact', $factura)
                            ->where('tipo_venta', session('impresion_seleccionado'))
                            ->sum('imp');
        $datos['total'] = TicketProducto::where('num_fact', $factura)
                            ->where('tipo_venta', session('impresion_seleccionado'))
                            ->sum('total');

                            
        $pago = TicketNum::select('efectivo', 'propina_cant', 'propina_porcent', 'total')
                            ->where('tipo_pago', session('tipo_pago'))
                            ->where('tipo_venta', session('impresion_seleccionado'))
                            ->where('factura', $factura)
                            ->first();
        // dd($pago);         
        $datos['efectivo'] = $pago->efectivo;
        $datos['propina_cant'] = $pago->propina_cant;
        $datos['propina_porcent'] = $pago->propina_porcent;

        $datos['cambio'] = $pago->efectivo - $pago->total;
        return $datos;
    }    



    public function getProductosComanda($orden, $estado, $panel){
        $datos =  TicketProducto::where('orden', $orden)
        ->where('imprimir', $estado)
        ->where('panel', $panel)
        ->with('subOpcion')->get();

        if (session('impresion_comanda_agrupada')) {
            return $this->formatData($datos);
        } else {
            return $this->formatDataComanda($datos);
        }
    }

    public function productosActualizar($orden, $anterior, $nuevo, $panel){
        TicketProducto::where('orden', $orden)
                        ->where('imprimir', $anterior)
                        ->update(['imprimir' => $nuevo, 'panel' => $panel, 'tiempo' => Helpers::timeId()]);
    }

    public function contarProductos($imprimir){
        return TicketProducto::where('orden', session('orden'))
                            ->where('num_fact', NULL)
                            ->where('imprimir', $imprimir)
                            ->count();
    }


    public function contarProductosPanel($imprimir, $panel){
        return TicketProducto::where('orden', session('orden'))
                            ->where('num_fact', NULL)
                            ->where('imprimir', $imprimir)
                            ->where('panel', $panel)
                            ->count();
    }




    public function detallesMesa($orden){
        $datos = array();
        $data = TicketOrden::select('nombre_mesa', 'comentario')->where('id', $orden)->first();
        $datos['nombre_mesa'] = $data->nombre_mesa;
        $datos['comentario'] = $data->comentario;

        return $datos;  
    }    
    

}