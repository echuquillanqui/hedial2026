<?php

namespace App\Http\Controllers;

use App\Models\LaboratoryOrder;
use App\Models\LaboratoryOrderItem;
use App\Models\Profile;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ZipArchive;

class LaboratoryOrderController extends Controller
{
    public function create()
    {
        $tests = Test::orderBy('name')->get(['id', 'name']);
        $profiles = Profile::with('tests:id,name')->orderBy('name')->get();

        return view('laboratory.orders.create', compact('tests', 'profiles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_name' => 'required|string|max:255',
            'requested_by' => 'nullable|string|max:120',
            'test_ids' => 'required|array|min:1',
            'test_ids.*' => 'integer|exists:tests,id',
        ]);

        DB::transaction(function () use ($data) {
            $order = LaboratoryOrder::create([
                'patient_name' => $data['patient_name'],
                'requested_by' => $data['requested_by'] ?? null,
            ]);

            $items = collect($data['test_ids'])->unique()->map(fn ($testId) => [
                'test_id' => $testId,
            ])->values()->all();

            $order->items()->createMany($items);
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

    public function results()
    {
        $orders = LaboratoryOrder::with(['items.test'])
            ->latest()
            ->get();

        return view('laboratory.results.index', compact('orders'));
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
