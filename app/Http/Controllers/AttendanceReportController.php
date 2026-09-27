<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Support\CurrentSede;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:audit.view');
    }

    public function index(Request $request)
    {
        [$month, $days] = $this->filters($request);
        $patients = $this->patientQuery($request, $month)->paginate(25)->withQueryString();
        $this->attachAttendances($patients->getCollection(), $month);

        return view('reports.attendances', [
            'patients' => $patients,
            'month' => $month,
            'days' => $days,
            'total' => $patients->total(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$month, $days] = $this->filters($request);
        $patients = $this->patientQuery($request, $month)->get();
        $this->attachAttendances($patients, $month);
        $filename = 'control-atenciones-hemodialisis-'.$month->format('Y-m').'.xls';

        return response()->streamDownload(function () use ($patients, $month, $days) {
            $escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
            echo "\xEF\xBB\xBF";
            echo '<html><head><meta charset="UTF-8"><style>td.text{mso-number-format:"\\@"}.yes{color:#198754}.no{color:#dc3545}</style></head><body>';
            echo '<table border="1"><tr><th colspan="'.(4 + $days->count()).'">Control de atenciones de hemodiálisis - '.$escape($month->translatedFormat('F Y')).'</th></tr><tr>';
            foreach (['Paciente', 'DNI', 'Módulo', 'Turno'] as $heading) {
                echo '<th>'.$escape($heading).'</th>';
            }
            foreach ($days as $day) {
                echo '<th>'.$day->format('d').'</th>';
            }
            echo '</tr>';
            foreach ($patients as $patient) {
                echo '<tr><td>'.$escape($patient->full_name).'</td><td class="text">'.$escape($patient->dni).'</td><td>'.$escape($patient->modulo).'</td><td>'.$escape($patient->turno).'</td>';
                foreach ($days as $day) {
                    $attended = $patient->attendance_days->has($day->day);
                    echo '<td class="'.($attended ? 'yes' : 'no').'">'.($attended ? '✓ ASISTENCIA' : '✗ FALTA').'</td>';
                }
                echo '</tr>';
            }
            echo '</table></body></html>';
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    /** @return array{Carbon, Collection<int, Carbon>} */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'search' => ['nullable', 'string', 'max:100'],
            'secuencia' => ['nullable', Rule::in(['L-M-V', 'M-J-S'])],
            'modulo' => ['nullable', Rule::in(Patient::MODULES)],
            'turno' => ['nullable', Rule::in(['1', '2', '3', '4'])],
        ]);
        $month = Carbon::createFromFormat('!Y-m', $validated['month'] ?? today()->format('Y-m'))->startOfMonth();
        $days = collect(range(1, $month->daysInMonth))->map(fn (int $day) => $month->copy()->day($day));

        return [$month, $days];
    }

    private function patientQuery(Request $request, Carbon $month): Builder
    {
        return Patient::query()
            ->when(CurrentSede::id(), fn (Builder $query, int $sede) => $query->where('sede_id', $sede))
            ->whereNotNull('modulo')->whereNotNull('turno')
            ->when($request->filled('secuencia'), fn (Builder $query) => $query->where('secuencia', $request->input('secuencia')))
            ->when($request->filled('modulo'), fn (Builder $query) => $query->where('modulo', $request->input('modulo')))
            ->when($request->filled('turno'), fn (Builder $query) => $query->where('turno', $request->input('turno')))
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = trim((string) $request->input('search'));
                $query->where(fn (Builder $match) => $match
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('other_names', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('dni', 'like', "%{$search}%"));
            })
            ->with(['orders' => fn ($query) => $query
                ->select('orders.id', 'orders.patient_id', 'orders.fecha_orden')
                ->where('attention_type', 'HEMODIALYSIS')
                ->finalizedHemodialysis()
                ->whereBetween('fecha_orden', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])])
            ->orderBy('modulo')->orderBy('turno')->orderBy('surname')->orderBy('last_name')->orderBy('first_name');
    }

    private function attachAttendances(Collection $patients, Carbon $month): void
    {
        $patients->each(function (Patient $patient) use ($month) {
            $patient->setAttribute('attendance_days', $patient->orders
                ->filter(fn ($order) => $order->fecha_orden?->isSameMonth($month))
                ->keyBy(fn ($order) => $order->fecha_orden->day));
        });
    }
}
