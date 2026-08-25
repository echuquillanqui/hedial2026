<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Patient;
use App\Support\ClinicalService;
use App\Support\CurrentSede;
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
        $allDates = $request->boolean('all_dates');
        $date = $allDates ? null : ($request->date('date')?->toDateString() ?? today()->toDateString());
        $missing = $request->input('missing');

        $consentIsMissing = function (Builder $query) use ($date) {
            $query->where('attention_type', ClinicalService::HEMODIALYSIS)
                ->when($date, fn (Builder $orders, string $value) => $orders->whereDate('fecha_orden', $value))
                ->whereDoesntHave('patient.hemodialysisConsents', function (Builder $consents) use ($date) {
                    $date
                        ? $consents->whereDate('consented_at', $date)
                        : $consents->whereRaw('DATE(hemodialysis_consents.consented_at) = DATE(orders.fecha_orden)');
                });
        };
        $consultationIsMissing = fn (Builder $query) => $query
            ->where('attention_type', ClinicalService::NEPHROLOGY)
            ->when($date, fn (Builder $orders, string $value) => $orders->whereDate('fecha_orden', $value))
            ->whereDoesntHave('nephrologyConsultation');
        $laboratoryIsMissing = fn (Builder $query) => $query
            ->where('attention_type', ClinicalService::HEMODIALYSIS)
            ->when($date, fn (Builder $orders, string $value) => $orders->whereDate('fecha_orden', $value))
            ->whereNotNull('laboratory_period')
            ->whereDoesntHave('laboratoryOrder');

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
            ->whereHas('orders', match ($missing) {
                'consent' => $consentIsMissing,
                'consultation' => $consultationIsMissing,
                'laboratory' => $laboratoryIsMissing,
                default => fn (Builder $orders) => $orders->where(function (Builder $orders) use ($consentIsMissing, $consultationIsMissing, $laboratoryIsMissing) {
                    $orders->where($consentIsMissing)->orWhere($consultationIsMissing)->orWhere($laboratoryIsMissing);
                }),
            })
            ->with(['orders' => fn ($query) => $query
                ->when($date, fn ($orders, string $value) => $orders->whereDate('fecha_orden', $value))
                ->whereIn('attention_type', [ClinicalService::HEMODIALYSIS, ClinicalService::NEPHROLOGY])
                ->with(['nephrologyConsultation', 'laboratoryOrder']),
                'hemodialysisConsents' => fn ($query) => $query
                    ->when($date, fn ($consents, string $value) => $consents->whereDate('consented_at', $value)),
            ])
            ->orderBy('surname')->orderBy('last_name')->orderBy('first_name')
            ->paginate(25)->withQueryString();

        return view('audit.pending-documents', compact('patients', 'date', 'allDates'));
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
