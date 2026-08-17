<?php

namespace App\Http\Controllers;

use App\Models\Fua;
use App\Models\FuaConfiguration;
use App\Models\Test;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FuaController extends Controller
{
    public function hemodialysisIndex(Request $request)
    {
        return $this->printIndex($request, Fua::HEMODIALYSIS);
    }

    public function nephrologyIndex(Request $request)
    {
        return $this->printIndex($request, Fua::NEPHROLOGY);
    }

    private function printIndex(Request $request, string $type)
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date'],
            'patient' => ['nullable', 'string', 'max:100'],
            'modulo' => ['nullable', 'integer', 'between:1,4'],
            'turno' => ['nullable', 'integer', 'between:1,4'],
            'all_dates' => ['nullable', 'boolean'],
        ]);

        $date = $request->boolean('all_dates') ? null : ($filters['date'] ?? now()->toDateString());
        $fuas = $this->printQuery(
            $type,
            $date,
            $filters['patient'] ?? null,
            $filters['modulo'] ?? null,
            $filters['turno'] ?? null,
        )
            ->orderByDesc('orders.fecha_orden')
            ->orderByDesc('fuas.id')
            ->select('fuas.*')
            ->paginate(30)
            ->withQueryString();

        return view('fuas.print-index', [
            'fuas' => $fuas,
            'date' => $date,
            'type' => $type,
        ]);
    }

    public function bulkPdf(Request $request)
    {
        return $this->bulkPdfForType($request, Fua::HEMODIALYSIS);
    }

    public function nephrologyBulkPdf(Request $request)
    {
        return $this->bulkPdfForType($request, Fua::NEPHROLOGY);
    }

    private function bulkPdfForType(Request $request, string $type)
    {
        $data = $request->validate([
            'fuas' => ['required', 'array', 'min:1'],
            'fuas.*' => ['integer', 'distinct'],
        ]);

        $fuas = Fua::query()
            ->where('type', $type)
            ->whereIn('id', $data['fuas'])
            ->with($this->pdfRelations())
            ->get()
            ->sortBy(fn (Fua $fua) => array_search($fua->id, $data['fuas']))
            ->values();

        abort_if($fuas->isEmpty(), 404);

        $configuration = FuaConfiguration::global();
        $documents = $fuas->map(fn (Fua $fua) => [
            'fua' => $fua,
            'responsible' => $fua->responsibleUser ?: $fua->order?->medical?->usuarioInicia,
            'medications' => $this->medications($fua),
            'procedures' => $this->procedures($fua),
        ]);

        $view = $type === Fua::NEPHROLOGY ? 'fuas.pdf_nephrology' : 'fuas.pdf';

        return Pdf::loadView($view, [
            'documents' => $documents,
            'configuration' => $configuration,
            'logoData' => $this->logoData($configuration->logo_path),
        ])->setPaper('a4')->stream($type === Fua::NEPHROLOGY ? 'fuas-consultas.pdf' : 'fuas-hemodialisis.pdf');
    }

    private function printQuery(
        string $type,
        ?string $date,
        ?string $patient,
        ?int $module,
        ?int $shift,
    ): Builder
    {
        return Fua::query()
            ->with(['order.patient', 'order.sede'])
            ->join('orders', 'orders.id', '=', 'fuas.order_id')
            ->where('fuas.type', $type)
            ->when($date, fn (Builder $query) => $query->whereDate('orders.fecha_orden', $date))
            ->when($shift, fn (Builder $query) => $query->where('orders.turno', (string) $shift))
            ->when($module, function (Builder $query, int $module) use ($type) {
                if ($type === Fua::NEPHROLOGY) {
                    $query->whereHas('order.patient', fn (Builder $patientQuery) => $patientQuery
                        ->where('modulo', (string) $module));

                    return;
                }

                $query->where('orders.sala', 'MODULO '.$module);
            })
            ->when($patient, function (Builder $query, string $patient) {
                $query->whereHas('order.patient', fn (Builder $query) => $query
                    ->where('dni', 'like', "%{$patient}%")
                    ->orWhere('surname', 'like', "%{$patient}%")
                    ->orWhere('last_name', 'like', "%{$patient}%")
                    ->orWhere('first_name', 'like', "%{$patient}%")
                    ->orWhere('other_names', 'like', "%{$patient}%"));
            });
    }

    public function index(Request $request)
    {
        $fuas = Fua::with(['order.patient', 'order.sede'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(function ($query) use ($search) {
                    $query->where('number', 'like', "%{$search}%")
                        ->orWhereHas('order.patient', fn ($patient) => $patient
                            ->where('dni', 'like', "%{$search}%")
                            ->orWhere('surname', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->type))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('fuas.index', compact('fuas'));
    }

    public function preview(Fua $fua)
    {
        $fua->load(['order.patient', 'order.sede', 'order.medical.usuarioInicia', 'responsibleUser']);
        $doctors = User::query()
            ->where(function ($query) {
                $query->whereHas('roles', fn ($roles) => $roles->where('name', 'medico'))
                    ->orWhere('profession', 'like', '%medic%')
                    ->orWhere('profession', 'like', '%médic%')
                    ->orWhere('profession', 'like', '%nefro%');
            })
            ->orderBy('name')
            ->get();

        return view('fuas.preview', compact('fua', 'doctors'));
    }

    public function updateResponsible(Request $request, Fua $fua)
    {
        $data = $request->validate([
            'responsible_user_id' => ['nullable', 'exists:users,id'],
        ]);

        if (! empty($data['responsible_user_id'])) {
            $doctor = User::findOrFail($data['responsible_user_id']);
            $profession = mb_strtolower($doctor->profession ?? '');
            abort_unless($doctor->hasRole('medico') || str_contains($profession, 'medic') || str_contains($profession, 'médic') || str_contains($profession, 'nefro'), 422);
        }

        $fua->update($data);

        return back()->with('success', 'Médico responsable actualizado en la FUA.');
    }

    public function pdf(Request $request, Fua $fua)
    {
        $fua->load($this->pdfRelations());
        $responsible = $fua->responsibleUser ?: $fua->order?->medical?->usuarioInicia;
        $medications = $this->medications($fua);
        $procedures = $this->procedures($fua);
        $configuration = FuaConfiguration::global();
        $view = $fua->type === 'NEPHROLOGY' ? 'fuas.pdf_nephrology' : 'fuas.pdf';
        $logoData = $this->logoData($configuration->logo_path);
        $document = Pdf::loadView($view, [
            'fua' => $fua,
            'configuration' => $configuration,
            'logoData' => $logoData,
            'medications' => $medications,
            'responsible' => $responsible,
            'procedures' => $procedures,
        ])->setPaper('a4');
        $filename = 'fua-'.str_replace(['/', '\\'], '-', $fua->number).'.pdf';

        return $request->boolean('download')
            ? $document->download($filename)
            : $document->stream($filename);
    }

    private function pdfRelations(): array
    {
        return [
            'order.patient', 'order.sede', 'order.medical.usuarioInicia',
            'order.laboratoryOrder.items.test', 'order.nephrologyConsultation.medications',
            'responsibleUser', 'correctedFua',
        ];
    }

    private function medications(Fua $fua): array
    {
        if ($fua->type === Fua::NEPHROLOGY) {
            return $fua->order?->nephrologyConsultation?->medications
                ?->map(fn ($medication) => [
                    'code' => $medication->fua_code,
                    'description' => $medication->description,
                    'prescribed_quantity' => $medication->prescribed_quantity,
                    'delivered_quantity' => $medication->delivered_quantity,
                ])->all() ?? [];
        }

        $medical = $fua->order?->medical;

        return collect([
            ['code' => '3107', 'description' => 'Epoetina alfa (eritropoyetina) 2 000 UI/ml, inyectable', 'quantity' => $medical?->epo2000],
            ['code' => '3113', 'description' => 'Epoetina alfa (eritropoyetina) 4 000 UI/ml, inyectable', 'quantity' => $medical?->epo4000],
            ['code' => '3979', 'description' => 'Vitamina B12 (hidroxicobalamina) 1 mg/ml, inyectable', 'quantity' => $medical?->vitamina_b12],
            ['code' => '19238', 'description' => 'Hierro (como sacarato) 20 mg Fe/ml, inyectable', 'quantity' => $medical?->hierro],
            ['code' => '01502', 'description' => 'Calcitriol 1 mcg/ml, inyectable', 'quantity' => $medical?->calcitriol],
        ])->filter(fn (array $medication) => is_numeric($medication['quantity']) && (float) $medication['quantity'] > 0)
            ->values()
            ->all();
    }

    private function logoData(?string $path): ?string
    {
        $absolutePath = $path
            ? storage_path('app/public/'.$path)
            : public_path('logo/logo-fissal.png');

        if (! is_file($absolutePath)) {
            $absolutePath = public_path('logo/logo-fissal.png');
        }

        if (! is_file($absolutePath)) {
            return null;
        }

        $mime = mime_content_type($absolutePath) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($absolutePath));
    }

    private function procedures(Fua $fua): array
    {
        if ($fua->type === Fua::NEPHROLOGY) {
            return [[
                'code' => '90937',
                'description' => 'Consulta ambulatoria especializada para la evaluación y manejo de un paciente continuador',
                'quantity' => 1,
            ]];
        }

        $rows = [['code' => '90937', 'description' => 'Procedimiento de hemodiálisis que requiere repetida(s) evaluación(es) con o sin', 'quantity' => 1]];
        $frequencies = match ($fua->order?->laboratory_period) {
            'M' => ['M'],
            'B' => ['M', 'B'],
            'T' => ['M', 'B', 'T'],
            'S' => ['M', 'B', 'T', 'S'],
            default => [],
        };
        $tests = Test::query()->where('is_fissal', true)->whereIn('frequency', $frequencies)->get();
        $urea = $tests->filter(fn (Test $test) => str_contains(mb_strtolower($test->name), 'urea'));

        if ($urea->isNotEmpty()) {
            $rows[] = ['code' => '84520', 'description' => 'Nitrógeno ureico; cuantitativo (Urea sérica)', 'quantity' => $urea->sum(fn (Test $test) => $test->fua_quantity ?? 1)];
        }

        $electrolyteNames = ['sodio', 'potasio', 'cloro'];
        $electrolytes = $tests->filter(fn (Test $test) => in_array(mb_strtolower(trim($test->name)), $electrolyteNames, true));

        if ($electrolytes->isNotEmpty()) {
            $rows[] = [
                'code' => $electrolytes->first()->code ?? '',
                'description' => 'Perfil de electrolitos (sodio, potasio y cloro)',
                'quantity' => 1,
            ];
        }

        foreach ($tests->reject(function (Test $test) use ($electrolyteNames) {
            $name = mb_strtolower(trim($test->name));

            return str_contains($name, 'urea') || in_array($name, $electrolyteNames, true);
        }) as $test) {
            $rows[] = [
                'code' => $test->code ?? '',
                'description' => $test->name,
                'quantity' => $test->fua_quantity ?? 1,
            ];
        }

        return $rows;
    }
}
