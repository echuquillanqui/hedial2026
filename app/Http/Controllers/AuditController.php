<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Patient;
use App\Support\ClinicalService;
use App\Support\CurrentSede;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:audit.view');
    }

    public function histories(Request $request)
    {
        $orders = $this->filteredOrders($request)
            ->with(['patient', 'medical', 'nurse', 'treatments'])
            ->orderBy('fecha_orden', 'desc')
            ->orderBy('sala')
            ->orderBy('turno')
            ->orderBy(
                Patient::select('surname')->whereColumn('patients.id', 'orders.patient_id')
            )
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
            ->orderBy('sala')
            ->orderBy('turno')
            ->orderBy(
                Patient::select('surname')->whereColumn('patients.id', 'orders.patient_id')
            )
            ->paginate(25)
            ->withQueryString();

        return view('audit.fissal', compact('orders'));
    }

    public function pendingDocuments(Request $request)
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'missing' => ['nullable', 'in:consent,consultation,laboratory,all'],
        ]);
        $month = $validated['month'] ?? today()->format('Y-m');
        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $missing = $request->input('missing');

        $hasConsentThisMonth = fn (Builder $query) => $query->whereBetween('consented_at', [$monthStart, $monthEnd]);
        $hasConsultationThisMonth = fn (Builder $query) => $query
            ->where('attention_type', ClinicalService::NEPHROLOGY)
            ->whereHas('nephrologyConsultation', fn (Builder $consultation) => $consultation
                ->whereBetween('consultation_date', [$monthStart, $monthEnd]));
        $hasLaboratoryThisMonth = fn (Builder $query) => $query->whereBetween('sampled_at', [$monthStart, $monthEnd]);

        $patients = Patient::query()
            ->when(CurrentSede::id(), fn (Builder $query, int $sede) => $query->where('sede_id', $sede))
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = trim((string) $request->input('search'));
                $query->where(fn (Builder $patient) => $patient
                    ->where('dni', 'like', "%{$search}%")
                    ->orWhere('medical_history_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%"));
            })
            ->when($request->filled('sequence'), fn (Builder $query) => $query->where('secuencia', $request->input('sequence')))
            ->when($request->filled('shift'), fn (Builder $query) => $query->where('turno', $request->input('shift')))
            ->when($request->filled('module'), fn (Builder $query) => $query->where('modulo', $request->input('module')))
            // Se parte del padrón completo de la sede: no tener ninguna orden o documento
            // generado durante el mes significa que el paciente tiene todo pendiente.
            ->where(function (Builder $query) use ($missing, $hasConsentThisMonth, $hasConsultationThisMonth, $hasLaboratoryThisMonth) {
                match ($missing) {
                    'consent' => $query->whereDoesntHave('hemodialysisConsents', $hasConsentThisMonth),
                    'consultation' => $query->whereDoesntHave('orders', $hasConsultationThisMonth),
                    'laboratory' => $query->whereDoesntHave('laboratoryOrders', $hasLaboratoryThisMonth),
                    'all' => $query
                        ->whereDoesntHave('hemodialysisConsents', $hasConsentThisMonth)
                        ->whereDoesntHave('orders', $hasConsultationThisMonth)
                        ->whereDoesntHave('laboratoryOrders', $hasLaboratoryThisMonth),
                    default => $query
                        ->whereDoesntHave('hemodialysisConsents', $hasConsentThisMonth)
                        ->orWhereDoesntHave('orders', $hasConsultationThisMonth)
                        ->orWhereDoesntHave('laboratoryOrders', $hasLaboratoryThisMonth),
                };
            })
            ->with(['orders' => fn ($query) => $query
                ->where(function (Builder $orders) use ($monthStart, $monthEnd) {
                    $orders->where(fn (Builder $hemodialysis) => $hemodialysis
                        ->where('attention_type', ClinicalService::HEMODIALYSIS)
                        ->whereBetween('fecha_orden', [$monthStart, $monthEnd]))
                        ->orWhere(fn (Builder $nephrology) => $nephrology
                            ->where('attention_type', ClinicalService::NEPHROLOGY)
                            ->whereHas('nephrologyConsultation', fn (Builder $consultation) => $consultation
                                ->whereBetween('consultation_date', [$monthStart, $monthEnd])));
                })
                ->with(['nephrologyConsultation', 'laboratoryOrder']),
                'hemodialysisConsents' => fn ($query) => $query
                    ->whereBetween('consented_at', [$monthStart, $monthEnd]),
                'laboratoryOrders' => fn ($query) => $query
                    ->whereBetween('sampled_at', [$monthStart, $monthEnd]),
            ])
            ->orderBy('surname')->orderBy('last_name')->orderBy('first_name')
            ->paginate(25)->withQueryString();

        return view('audit.pending-documents', compact('patients', 'month'));
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
