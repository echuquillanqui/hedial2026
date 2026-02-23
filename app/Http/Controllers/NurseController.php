<?php

namespace App\Http\Controllers;

use App\Models\Nurse;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class NurseController extends Controller
{
    public function index(Request $request)
{
    $dateFilter = $request->get('date', date('Y-m-d'));

    $nurses = Nurse::with(['order.patient', 'enfermeroInicia', 'enfermeroFinaliza'])
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
                // Ajustado a tu base de datos de medicina: 'MODULO ' . $valor
                $q->where('sala', 'MODULO ' . $modulo);
            });
        })
        ->when($request->estado, function ($query, $estado) {
            if ($estado === 'finalizado') {
                $query->whereNotNull('enfermero_que_finaliza_id');
            } elseif ($estado === 'en_curso') {
                $query->whereNull('enfermero_que_finaliza_id');
            }
        })
        ->latest()
        ->paginate(15)
        ->appends($request->all());

    // ESTA PARTE ES LA QUE HACE QUE EL FILTRO SEA INTERACTIVO
    if ($request->ajax()) {
        return view('atenciones.enfermeria._table', compact('nurses'))->render();
    }

    return view('atenciones.enfermeria.index', compact('nurses'));
}

    public function edit(Nurse $nurse)
    {
        $nurse->load(['order.patient', 'order.medical', 'order.treatments']);
        $order = $nurse->order;

        // Si el numero_hd es nulo o cero, calculamos el correlativo real
        if (!$nurse->numero_hd) {
            $inicioMes = now()->startOfMonth();
            $finMes = now()->endOfMonth();

            // CONTAMOS registros ANTERIORES (excluyendo el actual si ya tiene ID)
            $conteoPrevio = Nurse::whereHas('order', function($q) use ($order) {
                    $q->where('patient_id', $order->patient_id);
                })
                ->whereBetween('created_at', [$inicioMes, $finMes])
                ->where('id', '!=', $nurse->id) // EXCLUIR EL ACTUAL
                ->count();

            $nurse->numero_hd = $conteoPrevio + 1;
            $nurse->save();
        }

        $enfermeros = User::all();
        return view('atenciones.enfermeria.edit', compact('nurse', 'order', 'enfermeros'));
    }

    public function update(Request $request, Nurse $nurse)
    {
        try {
            DB::transaction(function () use ($request, $nurse) {
                // Actualizamos la tabla nurses
                $nurse->update($request->all());

                // Actualizamos monitoreo horario (Treatments)
                if ($request->has('t_hora')) {
                    $nurse->order->treatments()->delete();
                    foreach ($request->t_hora as $key => $hora) {
                        if (!empty($hora)) {
                            $nurse->order->treatments()->create([
                                'hora'        => $hora,
                                'pa'          => $request->t_pa[$key] ?? null,
                                'fc'          => $request->t_fc[$key] ?? null,
                                'qb'          => $request->t_qb[$key] ?? null,
                                'cnd'         => $request->t_cnd[$key] ?? null,
                                'ra'          => $request->t_ra[$key] ?? null,
                                'rv'          => $request->t_rv[$key] ?? null,
                                'ptm'         => $request->t_ptm[$key] ?? null,
                                'observacion' => $request->t_obs[$key] ?? null,
                            ]);
                        }
                    }
                }
            });
            return response()->json(['status' => 'success', 'message' => 'Registro de Enfermería actualizado']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function printSingle($id)
    {
        $order = Order::with(['patient', 'medical', 'nurse', 'treatments'])->findOrFail($id);
        
        $date = \Carbon\Carbon::parse($order->fecha_orden)->format('d/m/Y');

        // Cargamos la vista que estamos construyendo bloque por bloque
        $pdf = Pdf::loadView('atenciones.enfermeria.print_single', compact('order', 'date'));

        // Usamos stream para previsualizar en el navegador sin descargar
        return $pdf->stream('Ficha_HD_'.$order->patient->dni.'.pdf');
    }

}