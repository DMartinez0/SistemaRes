<?php

namespace App\Http\Livewire\Panel;

use App\Models\TicketDelivery;
use App\Models\TicketOrden;
use App\System\Panel\DatosDia;
use App\System\Ventas\Ventas;
use Livewire\Component;
use Livewire\WithPagination;

class Ordenes extends Component
{
    use WithPagination;
    use DatosDia;
    use Ventas;

    protected $paginationTheme = 'bootstrap';


    public $totalOrdenes, $totalLlevar, $totalAqui;
    public $totalPendientes, $pendientesLlevar, $pendientesAqui;


    public function mount(){
        // $this->obtenerDatos();
        $this->otrosDatos();
    }

    

    public function render()
    {
        return view('livewire.panel.ordenes', [
            'datos' => $this->obtenerDatosOrdenesDiarios(date('d-m-Y'), 25)
        ]);
    }



    public function otrosDatos(){

        $this->totalOrdenes = TicketOrden::whereDay('created_at', date('d-m-Y'))->count();
        $this->totalLlevar = TicketOrden::where('llevar_aqui', 1)->whereDay('created_at', date('d-m-Y'))->count();
        $this->totalAqui = TicketOrden::where('llevar_aqui', 2)->whereDay('created_at', date('d-m-Y'))->count();

        $this->totalPendientes = TicketOrden::where('edo', 1)->whereDay('created_at', date('d-m-Y'))->count();
        $this->pendientesLlevar = TicketOrden::where('edo', 1)->where('llevar_aqui', 1)->whereDay('created_at', date('d-m-Y'))->count();
        $this->pendientesAqui = TicketOrden::where('edo', 1)->where('llevar_aqui', 2)->whereDay('created_at', date('d-m-Y'))->count();

    }

    public function selectOrden($orden, $tipo_servicio){
        session(['config_tipo_servicio' => $tipo_servicio]);
        session(['orden' => $orden]);

        if ($tipo_servicio == 3) {
            session(['clientes' => 1]);
   
            $client = TicketDelivery::select('cliente_id')
                            ->where('orden_id', session('orden'))->first();
            
            session(['client_select' => $client->cliente_id]);
            $this->getDeliveryData();
            session()->forget('client_select');
        }

        return redirect()->route('venta.rapida');
    }




}
