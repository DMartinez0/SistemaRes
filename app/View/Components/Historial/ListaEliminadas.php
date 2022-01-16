<?php

namespace App\View\Components\Historial;

use Illuminate\View\Component;

class ListaEliminadas extends Component
{
    public $datos;

    
    public function __construct($datos)
    {
        $this->datos = $datos;
    }
    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.historial.lista-eliminadas');
    }
}
