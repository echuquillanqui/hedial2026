<?php

namespace App\Http\Controllers;

use App\Models\MedicationCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MedicationCatalogController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:nephrology.update')->except('search');
        $this->middleware('permission:nephrology.view')->only('search');
    }

    public function index()
    {
        return view('medication-catalog.index', [
            'medications' => MedicationCatalog::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $catalog = MedicationCatalog::all()->keyBy('id');
        $data = $request->validate([
            'medications' => ['required', 'array', 'size:'.$catalog->count()],
            'medications.*.code' => ['nullable', 'string', 'max:30', 'distinct'],
            'medications.*.name' => ['required', 'string', 'max:255'],
            'medications.*.reference_quantity' => ['required', 'integer', 'min:1'],
            'medications.*.frequency' => ['required', 'string', 'max:30'],
        ]);

        abort_unless(collect(array_keys($data['medications']))->map(fn ($id) => (int) $id)->sort()->values()->all()
            === $catalog->keys()->map(fn ($id) => (int) $id)->sort()->values()->all(), 422);

        foreach ($data['medications'] as $id => $medication) {
            $medication['code'] = filled($medication['code']) ? trim($medication['code']) : null;
            $catalog[(int) $id]->update($medication);
        }

        return back()->with('success', 'Catálogo de medicamentos actualizado correctamente.');
    }

    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q'));

        return response()->json(MedicationCatalog::query()
            ->when($term !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")))
            ->orderBy('name')->limit(12)
            ->get(['id', 'code', 'name', 'reference_quantity', 'frequency']));
    }
}
