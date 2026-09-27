<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\CurrentSede;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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
        [$start, $end, $period] = $this->period($request);

        $orders = $this->attendanceQuery($request, $start, $end)
            ->paginate(25)
            ->withQueryString();

        $total = $orders->total();

        return view('reports.attendances', compact('orders', 'start', 'end', 'period', 'total'));
    }

    public function export(Request $request): StreamedResponse
    {
        [$start, $end, $period] = $this->period($request);
        $orders = $this->attendanceQuery($request, $start, $end)->get();
        $filename = 'atenciones-'.$period.'-'.$start->format('Y-m-d').'-'.$end->format('Y-m-d').'.xls';

        return response()->streamDownload(function () use ($orders, $start, $end) {
            $escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');

            echo "\xEF\xBB\xBF";
            echo '<html><head><meta charset="UTF-8"><style>td.number{mso-number-format:"\\@"}</style></head><body>';
            echo '<table border="1"><tr><th colspan="17">Reporte de atenciones FISSAL</th></tr>';
            echo '<tr><th colspan="2">Periodo</th><td colspan="15">'.$escape($start->format('d/m/Y')).' - '.$escape($end->format('d/m/Y')).'</td></tr>';
            echo '<tr>';
            foreach (['Paciente', 'DNI', 'Secuencia', 'Inicio', 'Final', 'N.° FUA', 'EPO 2000', 'EPO 4000', 'Vit. B12', 'Hierro', 'Calcitriol', 'Fecha', 'Lic. inicia', 'Lic. finaliza', 'Nefrólogo', 'Módulo', 'Turno'] as $heading) {
                echo '<th>'.$escape($heading).'</th>';
            }
            echo '</tr>';

            foreach ($orders as $order) {
                $nurse = $order->nurse;
                $medical = $order->medical;
                $values = [
                    $order->patient?->full_name,
                    $order->patient?->dni,
                    $order->patient?->secuencia,
                    optional($order->treatments->first())->hora ? substr($order->treatments->first()->hora, 0, 5) : '',
                    optional($order->treatments->last())->hora ? substr($order->treatments->last()->hora, 0, 5) : '',
                    $order->fua?->correlative,
                    $nurse?->epo2000 ?: 0,
                    $nurse?->epo4000 ?: 0,
                    $nurse?->vitamina_b12 ?: 0,
                    $nurse?->hierro ?: 0,
                    $nurse?->calcitriol ?: 0,
                    optional($order->fecha_orden)->format('d/m/Y'),
                    $nurse?->enfermeroInicia?->name,
                    $nurse?->enfermeroFinaliza?->name,
                    $medical?->usuarioInicia?->name ?: $medical?->usuarioFinaliza?->name ?: '—',
                    $order->sala,
                    $order->turno,
                ];

                echo '<tr>';
                foreach ($values as $index => $value) {
                    $class = in_array($index, [1, 5], true) ? ' class="number"' : '';
                    echo '<td'.$class.'>'.$escape($value).'</td>';
                }
                echo '</tr>';
            }
            echo '</table></body></html>';
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    /** @return array{Carbon, Carbon, string} */
    private function period(Request $request): array
    {
        $validated = $request->validate([
            'period' => ['nullable', Rule::in(['day', 'week', 'month'])],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'week' => ['nullable', 'regex:/^\d{4}-W\d{2}$/'],
            'month' => ['nullable', 'date_format:Y-m'],
            'search' => ['nullable', 'string', 'max:100'],
            'secuencia' => ['nullable', Rule::in(['L-M-V', 'M-J-S'])],
            'modulo' => ['nullable', Rule::in(['1', '2', '3', '4'])],
            'turno' => ['nullable', Rule::in(['1', '2', '3', '4'])],
        ]);
        $period = $validated['period'] ?? 'day';

        if ($period === 'week') {
            $week = $validated['week'] ?? today()->format('o-\WW');
            preg_match('/^(\d{4})-W(\d{2})$/', $week, $parts);
            $start = Carbon::now()->setISODate((int) $parts[1], (int) $parts[2])->startOfDay();

            return [$start, $start->copy()->endOfWeek(), $period];
        }

        if ($period === 'month') {
            $start = Carbon::createFromFormat('!Y-m', $validated['month'] ?? today()->format('Y-m'))->startOfMonth();

            return [$start, $start->copy()->endOfMonth(), $period];
        }

        $start = Carbon::createFromFormat('!Y-m-d', $validated['date'] ?? today()->toDateString())->startOfDay();

        return [$start, $start->copy()->endOfDay(), $period];
    }

    private function attendanceQuery(Request $request, Carbon $start, Carbon $end): Builder
    {
        return Order::query()
            ->where('attention_type', 'HEMODIALYSIS')
            ->finalizedHemodialysis()
            ->when(CurrentSede::id(), fn (Builder $query, int $sede) => $query->where('sede_id', $sede))
            ->whereBetween('fecha_orden', [$start->toDateString(), $end->toDateString()])
            ->when($request->filled('secuencia'), fn (Builder $query) => $query->whereHas('patient', fn (Builder $patient) => $patient->where('secuencia', $request->input('secuencia'))))
            ->when($request->filled('modulo'), fn (Builder $query) => $query->where('sala', 'MODULO '.$request->input('modulo')))
            ->when($request->filled('turno'), fn (Builder $query) => $query->where('turno', $request->input('turno')))
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = trim((string) $request->input('search'));
                $query->where(fn (Builder $match) => $match
                    ->where('codigo_unico', 'like', "%{$search}%")
                    ->orWhereHas('patient', fn (Builder $patient) => $patient
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('other_names', 'like', "%{$search}%")
                        ->orWhere('surname', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('dni', 'like', "%{$search}%")));
            })
            ->with([
                'patient', 'fua', 'medical.usuarioInicia', 'medical.usuarioFinaliza',
                'nurse.enfermeroInicia', 'nurse.enfermeroFinaliza',
                'treatments' => fn ($query) => $query->orderBy('hora'),
            ])
            ->orderByDesc('fecha_orden')
            ->orderBy('sala')
            ->orderBy('turno')
            ->orderBy('id');
    }
}
