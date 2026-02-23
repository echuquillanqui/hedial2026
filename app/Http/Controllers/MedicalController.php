<?php

namespace App\Http\Controllers;

use App\Models\Medical;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MedicalController extends Controller
{
    /**
     * Listado con búsqueda interactiva y filtros.
     */
    public function index(Request $request)
    {
        $dateFilter = $request->get('date', date('Y-m-d'));

        $medicals = Medical::with(['order.patient', 'usuarioInicia', 'usuarioFinaliza'])
            ->when($request->search, function ($query, $search) {
                $query->whereHas('order.patient', function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('surname', 'like', "%{$search}%")
                      ->orWhere('dni', 'like', "%{$search}%");
                })->orWhereHas('order', function($q) use ($search) {
                    $q->where('codigo_unico', 'like', "%{$search}%");
                });
            })
            ->when($dateFilter, function ($query, $date) {
                $query->whereHas('order', function($q) use ($date) {
                    $q->whereDate('fecha_orden', $date);
                });
            })
            ->when($request->turno, function ($query, $turno) {
                $query->whereHas('order', function($q) use ($turno) {
                    $q->where('turno', $turno);
                });
            })
            ->when($request->modulo, function ($query, $modulo) {
                $query->whereHas('order', function($q) use ($modulo) {
                    $q->where('sala', 'MODULO ' . $modulo);
                });
            })
            ->when($request->estado, function ($query, $estado) {
                if ($estado === 'finalizado') {
                    $query->whereNotNull('hora_final');
                } elseif ($estado === 'en_curso') {
                    $query->whereNull('hora_final');
                }
            })
            ->latest()
            ->paginate(15)
            ->appends($request->all());

        return view('atenciones.medicina.index', compact('medicals'));
    }

    /**
     * Formulario de edición con selección de médicos.
     */
    public function edit(Medical $medical)
    {
        $order = $medical->order;
        
        // Obtenemos solo los usuarios cuya profesión sea MEDICO
        $medicos = User::where('profession', 'MEDICO')->get();
        
        return view('atenciones.medicina.edit', compact('medical', 'order', 'medicos'));
    }

    /**
     * Actualización integral de la ficha médica.
     */
    public function update(Request $request, Medical $medical)
    {

        $validated = $request->validate([
            // Signos Vitales e Iniciales (Migración)
            'hora_inicial'        => 'nullable',
            'peso_inicial'        => 'nullable|numeric',
            'pa_inicial'          => 'nullable|string',
            'frecuencia_cardiaca' => 'nullable|integer',
            'so2'                 => 'nullable|integer',
            'fio2'                => 'nullable|numeric',
            'temperatura'         => 'nullable|numeric',
            
            // Textos Clínicos
            'problemas_clinicos'  => 'nullable|string',
            'evaluacion'          => 'nullable|string',
            'indicaciones'        => 'nullable|string',
            'signos_sintomas'     => 'nullable|string',

            // Medicación (Migración)
            'epo2000'             => 'nullable|string',
            'epo4000'             => 'nullable|string',
            'hierro'              => 'nullable|string',
            'vitamina_b12'        => 'nullable|string',
            'calcitriol'          => 'nullable|string',
            'heparina'            => 'nullable|string',

            // Parámetros Técnicos
            'hora_hd'             => 'required|numeric',
            'peso_seco'           => 'nullable|numeric',
            'uf'                  => 'required|string|max:20',
            'qb'                  => 'nullable|integer',
            'qd'                  => 'nullable|integer',
            'bicarbonato'         => 'nullable|integer',
            'na_inicial'          => 'nullable|integer',
            'cnd'                 => 'nullable|numeric',
            'na_final'            => 'nullable|integer',
            'perfil_na'           => 'nullable|string',
            'area_filtro'         => 'nullable|string',
            'membrana'            => 'nullable|string',
            'perfil_uf'           => 'nullable|string',

            // Cierre y Responsables
            'evaluacion_final'    => 'nullable|string',
            'hora_final'          => 'nullable',
            'usuario_que_inicia_hd'   => 'nullable|exists:users,id',
            'usuario_que_finaliza_hd' => 'nullable|exists:users,id',
        ]);
        

        // Si no se selecciona un médico de inicio, se asigna el usuario actual por defecto
        if (!$request->filled('usuario_que_inicia_hd') && !$medical->usuario_que_inicia_hd) {
            $validated['usuario_que_inicia_hd'] = Auth::id();
        }

        $medical->update($validated);

        return redirect()->route('medicals.index')
            ->with('success', 'Ficha médica actualizada correctamente.');
    }

    public function show(Medical $medical)
    {
        // Cargamos la orden y el paciente para obtener sala, turno y fecha
        $medical->load(['order.patient']);

        if (request()->ajax()) {
            $html = '
            <div class="row g-3">
                <div class="col-md-6"><strong>Fecha:</strong><br> ' . \Carbon\Carbon::parse($medical->created_at)->format('d/m/Y') . '</div>
                <div class="col-md-3"><strong>Sala:</strong><br> ' . ($medical->order->sala ?? '---') . '</div>
                <div class="col-md-3"><strong>Turno:</strong><br> ' . ($medical->order->turno ?? '---') . '</div>
                
                <div class="col-12"><hr class="my-1"></div>

                <div class="col-md-3"><strong>Peso Inicial:</strong><br> ' . ($medical->peso_inicial ?: '0') . ' kg</div>
                <div class="col-md-3"><strong>Peso Seco:</strong><br> ' . ($medical->peso_seco ?: '0') . ' kg</div>
                <div class="col-md-3"><strong>UF:</strong><br> <span class="text-primary fw-bold">' . ($medical->uf ?: '---') . '</span></div>
                <div class="col-md-3"><strong>Hora HD:</strong><br> ' . ($medical->hora_hd ?: '---') . ' hrs</div>

                <div class="col-12"><hr class="my-1"></div>
                
                <div class="col-12">
                    <label class="fw-bold text-success small">MEDICAMENTOS APLICADOS:</label>
                    <div class="p-2 border rounded bg-light">
                        <div class="row">
                            <div class="col-md-4"><strong>EPO 2000:</strong> ' . ($medical->epo2000 ?: '0') . '</div>
                            <div class="col-md-4"><strong>EPO 4000:</strong> ' . ($medical->epo4000 ?: '0') . '</div>
                            <div class="col-md-4"><strong>Hierro:</strong> ' . ($medical->hierro ?: '0') . '</div>
                            <div class="col-md-4"><strong>Vit. B12:</strong> ' . ($medical->vitamina_b12 ?: '0') . '</div>
                            <div class="col-md-4"><strong>Calcitriol:</strong> ' . ($medical->calcitriol ?: '0') . '</div>
                        </div>
                    </div>
                </div>
            </div>';

            return response($html);
        }

        return redirect()->route('medicals.index');
    }
}