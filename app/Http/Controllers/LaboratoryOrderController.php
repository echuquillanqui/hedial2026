<?php

namespace App\Http\Controllers;

use App\Models\LaboratoryOrder;
use App\Models\LaboratoryOrderItem;
use App\Models\Profile;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'patient_id' => 'required|exists:patients,id',
            'requested_by' => 'nullable|string|max:120',
            'test_ids' => 'required|array|min:1',
            'test_ids.*' => 'integer|exists:tests,id',
        ]);

        DB::transaction(function () use ($data) {
            $order = LaboratoryOrder::create([
                'patient_id' => $data['patient_id'],
                'requested_by' => $data['requested_by'] ?? null,
            ]);

            $items = collect($data['test_ids'])->unique()->map(fn ($testId) => [
                'test_id' => $testId,
            ])->values()->all();

            $order->items()->createMany($items);
        });

        return redirect()->route('laboratory.results.index')->with('success', 'Orden de laboratorio registrada correctamente.');
    }

    public function results()
    {
        $orders = LaboratoryOrder::with(['patient', 'items.test'])
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
}
