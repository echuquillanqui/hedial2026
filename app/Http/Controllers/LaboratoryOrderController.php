<?php

namespace App\Http\Controllers;

use App\Models\LaboratoryOrder;
use App\Models\LaboratoryOrderItem;
use App\Models\Patient;
use App\Models\Profile;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ZipArchive;
use Barryvdh\DomPDF\Facade\Pdf;

class LaboratoryOrderController extends Controller
{
    public function create()
    {
        $tests = Test::with('area:id,name')->where('is_fissal', true)->orderBy('area_id')->orderBy('name')->get();
        $patients = Patient::orderBy('surname')->orderBy('first_name')->get();
        $profiles = Profile::with(['tests' => fn ($query) => $query->where('is_fissal', true)])
            ->orderBy('name')
            ->get();

        return view('laboratory.orders.create', compact('tests', 'patients', 'profiles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_ids' => 'required|array|min:1',
            'patient_ids.*' => 'integer|exists:patients,id',
            'requested_by' => 'nullable|string|max:120',
            'period' => 'required|in:M,B,T,S',
            'sampled_at' => 'required|date',
            'test_ids' => 'required|array|min:1',
            'test_ids.*' => 'integer|exists:tests,id',
        ]);

        DB::transaction(function () use ($data) {
            Patient::whereIn('id', $data['patient_ids'])->get()->each(function (Patient $patient) use ($data) {
                $order = LaboratoryOrder::create([
                    'patient_id' => $patient->id,
                    'patient_name' => $patient->full_name,
                    'requested_by' => $data['requested_by'] ?? null,
                    'period' => $data['period'],
                    'sampled_at' => $data['sampled_at'],
                    'provenance' => 'FISSAL',
                ]);
                $order->items()->createMany(collect($data['test_ids'])->unique()->map(fn ($id) => ['test_id' => $id])->all());
            });
        });

        return redirect()->route('laboratory.results.index')->with('success', 'Orden de laboratorio registrada correctamente.');
    }

    public function import(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx'],
        ]);

        $rows = $this->readSpreadsheetRows($data['file']->getRealPath(), $data['file']->getClientOriginalExtension());
        $header = array_shift($rows) ?? [];
        $columns = collect($header)->map(fn ($value) => $this->normalizeHeader($value));
        $nameIndex = $columns->search('nombres_y_apellidos');

        if ($nameIndex === false) {
            return back()->withErrors(['file' => 'El archivo debe incluir la columna nombres_y_apellidos.']);
        }

        $testsByName = Test::query()->get(['id', 'name'])->keyBy(fn (Test $test) => $this->normalizeHeader($test->name));
        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, $columns, $nameIndex, $testsByName, &$imported, &$skipped) {
            foreach ($rows as $row) {
                $patientName = trim((string) ($row[$nameIndex] ?? ''));

                if ($patientName === '') {
                    $skipped++;
                    continue;
                }

                $order = LaboratoryOrder::create([
                    'patient_name' => $patientName,
                    'status' => 'completed',
                ]);

                $items = [];
                foreach ($columns as $index => $column) {
                    if ($index === $nameIndex || ! $testsByName->has($column)) {
                        continue;
                    }

                    $value = trim((string) ($row[$index] ?? ''));
                    if ($value === '') {
                        continue;
                    }

                    $items[] = [
                        'test_id' => $testsByName[$column]->id,
                        'result_value' => $value,
                        'completed_at' => now(),
                    ];
                }

                if ($items === []) {
                    $order->update(['status' => 'pending']);
                } else {
                    $order->items()->createMany($items);
                }

                $imported++;
            }
        });

        return back()->with('success', "Importación completada: {$imported} pacientes importados, {$skipped} filas omitidas.");
    }

    public function results(Request $request)
    {
        $orders = LaboratoryOrder::with(['patient', 'items.test.area'])
            ->when($request->filled('q'), fn ($query) => $query->where('patient_name', 'like', '%'.$request->q.'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('period'), fn ($query) => $query->where('period', $request->period))
            ->latest()
            ->paginate(15)->withQueryString();

        return view('laboratory.results.index', compact('orders'));
    }

    public function show(LaboratoryOrder $laboratoryOrder)
    {
        $laboratoryOrder->load(['patient', 'items.test.area']);
        return view('laboratory.results.show', ['order' => $laboratoryOrder]);
    }

    public function pdf(LaboratoryOrder $laboratoryOrder)
    {
        $laboratoryOrder->load(['patient', 'items.test.area']);
        return Pdf::loadView('laboratory.results.pdf', ['orders' => collect([$laboratoryOrder])])
            ->setPaper('a4')->stream('laboratorio-'.$laboratoryOrder->id.'.pdf');
    }

    public function bulkPdf(Request $request)
    {
        $data = $request->validate(['order_ids' => 'required|array|min:1', 'order_ids.*' => 'exists:laboratory_orders,id']);
        $orders = LaboratoryOrder::with(['patient', 'items.test.area'])->whereIn('id', $data['order_ids'])->get();
        return Pdf::loadView('laboratory.results.pdf', compact('orders'))->setPaper('a4')->stream('laboratorios-fissal.pdf');
    }

    public function updateResults(Request $request, LaboratoryOrder $laboratoryOrder)
    {
        $payload = $request->validate([
            'results' => 'required|array',
            'results.*.value' => 'nullable|string|max:255',
            'results.*.notes' => 'nullable|string|max:1000',
        ]);

        foreach ($laboratoryOrder->items as $item) {
            $result = $payload['results'][$item->id] ?? null;
            if (! $result) {
                continue;
            }

            $item->update([
                'result_value' => $result['value'] ?? null,
                'result_notes' => $result['notes'] ?? null,
                'completed_at' => filled($result['value'] ?? null) ? now() : null,
            ]);
        }

        $hasPending = LaboratoryOrderItem::where('laboratory_order_id', $laboratoryOrder->id)
            ->whereNull('completed_at')
            ->exists();

        $laboratoryOrder->update(['status' => $hasPending ? 'pending' : 'completed']);

        return back()->with('success', 'Resultados actualizados.');
    }

    private function normalizeHeader(mixed $value): string
    {
        return Str::of((string) $value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();
    }

    private function readSpreadsheetRows(string $path, string $extension): array
    {
        return strtolower($extension) === 'xlsx'
            ? $this->readXlsxRows($path)
            : $this->readDelimitedRows($path);
    }

    private function readDelimitedRows(string $path): array
    {
        $handle = fopen($path, 'r');
        $sample = fgets($handle) ?: '';
        rewind($handle);
        $delimiter = substr_count($sample, ';') > substr_count($sample, ',') ? ';' : ',';
        $rows = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function readXlsxRows(string $path): array
    {
        if (! class_exists(ZipArchive::class)) {
            return [];
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            foreach (simplexml_load_string($sharedXml)->si as $item) {
                $sharedStrings[] = (string) ($item->t ?? $item->r->t ?? '');
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            return [];
        }

        $rows = [];
        foreach (simplexml_load_string($sheetXml)->sheetData->row as $rowNode) {
            $row = [];
            foreach ($rowNode->c as $cell) {
                $reference = (string) $cell['r'];
                $index = $this->columnIndexFromReference($reference);
                $value = (string) $cell->v;
                $row[$index] = ((string) $cell['t']) === 's' ? ($sharedStrings[(int) $value] ?? '') : $value;
            }
            if ($row === []) {
                $rows[] = [];
                continue;
            }

            $rows[] = array_map(fn ($index) => $row[$index] ?? '', range(0, max(array_keys($row))));
        }

        return $rows;
    }

    private function columnIndexFromReference(string $reference): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($reference));
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }
}
