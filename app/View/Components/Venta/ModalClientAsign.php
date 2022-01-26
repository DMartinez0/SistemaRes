<?php

namespace App\View\Components\Venta;

use Illuminate\View\Component;

class ModalClientAsign extends Component
{

    public $search, $busqueda;

    public function __construct($search, $busqueda)
    {
        $this->search = $search;
        $this->busqueda = $busqueda;
    }



    public function render()
    {
        return view('components.venta.modal-client-asign');
    }
}
