<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\CurrentSede;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function histories(Request $request)
    {
        $orders = $this->filteredOrders($request)
            ->with(['patient', 'medical', 'nurse', 'treatments'])
            ->orderBy('fecha_orden', 'desc')
            ->orderBy('turno')
            ->paginate(20)
            ->withQueryString();

        return view('audit.histories', compact('orders'));
    }

    public function fissal(Request $request)
    {
        $orders = $this->filteredOrders($request)
            ->with([
                'patient', 'fua', 'medical.usuarioInicia', 'medical.usuarioFinaliza',
                'nurse.enfermeroInicia', 'nurse.enfermeroFinaliza',
                'treatments' => fn ($query) => $query->orderBy('hora'),
            ])
            ->orderBy('fecha_orden', 'desc')
            ->orderBy('turno')
            ->paginate(25)
            ->withQueryString();

        return view('audit.fissal', compact('orders'));
    }

    private function filteredOrders(Request $request): Builder
    {
        $date = $request->input('date', today()->toDateString());

        return Order::query()
            ->where('attention_type', 'HEMODIALYSIS')
            ->when(CurrentSede::id(), fn ($query, $sedeId) => $query->where('sede_id', $sedeId))
            ->when($date, fn ($query) => $query->whereDate('fecha_orden', $date))
            ->when($request->filled('turno'), fn ($query) => $query->where('turno', $request->input('turno')))
            ->when($request->filled('modulo'), fn ($query) => $query->where('sala', 'MODULO '.$request->input('modulo')))
            ->when($request->filled('estado'), function ($query) use ($request) {
                $request->input('estado') === 'completo'
                    ? $query->whereHas('medical')->whereHas('nurse')->whereHas('treatments')
                    : $query->where(function ($query) {
                        $query->whereDoesntHave('medical')
                            ->orWhereDoesntHave('nurse')
                            ->orWhereDoesntHave('treatments');
                    });
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($query) use ($search) {
                    $query->where('codigo_unico', 'like', "%{$search}%")
                        ->orWhereHas('patient', fn ($patient) => $patient
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('surname', 'like', "%{$search}%")
                            ->orWhere('dni', 'like', "%{$search}%"));
                });
            });
    }
}
