<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Patient;

class HomeController extends Controller
{
    public function index(Request $request)
{
    $hoy = $request->get('date', now()->format('Y-m-d'));
    
    $todasLasOrdenes = Order::with(['patient', 'medical', 'nurse', 'treatments'])
                    ->whereDate('fecha_orden', $hoy)
                    ->orderBy('turno')
                    ->orderBy('sala')
                    ->get();

    $todasLasOrdenes->each(function($o) {
        // Marcamos como OK si tiene datos básicos de inicio
        $o->medical_ok = $o->medical && !empty($o->medical->pa_inicial);
        $o->nurse_ok = $o->nurse && !empty($o->nurse->puesto);
        $o->treatment_ok = $o->treatments->count() > 0;
        
        $o->todo_rellenado = ($o->medical_ok && $o->nurse_ok && $o->treatment_ok);
    });

    $pendientes = $todasLasOrdenes->filter(fn($o) => !$o->todo_rellenado);

    return view('home', [
        'ordenesCompletas' => $todasLasOrdenes,
        'ordenesPendientes' => $pendientes,
        'total' => $todasLasOrdenes->count(),
        'completos' => $todasLasOrdenes->count() - $pendientes->count(),
        'fechaActual' => $hoy
    ]);
}

    // Dentro de HomeController.php

private function checkTable($model) {
    // Definimos qué campos son OBLIGATORIOS para considerar que la ficha está llena
    // Si estos campos tienen datos, la ficha se considera OK
    $criticalFields = [
        'pa_inicial', 'peso_inicial', 'hora_inicial', 'qb', 'qd'
    ];

    foreach ($criticalFields as $field) {
        if (isset($model->$field) && (!empty($model->$field) || $model->$field > 0)) {
            continue; 
        } else {
            // Si el campo existe en el modelo pero está vacío, está incompleto
            if (array_key_exists($field, $model->getAttributes())) return false;
        }
    }
    return true;
}
}