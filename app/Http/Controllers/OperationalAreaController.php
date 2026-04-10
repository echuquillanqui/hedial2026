<?php

namespace App\Http\Controllers;

use App\Models\OperationalArea;
use App\Models\Sede;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OperationalAreaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:users.view')->only(['index']);
        $this->middleware('permission:users.edit')->only(['store', 'update']);
    }

    public function index()
    {
        $areas = OperationalArea::query()
            ->with(['sede', 'users:id,name'])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $sedes = Sede::query()->where('is_active', true)->orderBy('name')->get();

        return view('operational-areas.index', compact('areas', 'sedes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sede_id' => ['required', 'exists:sedes,id'],
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('operational_areas', 'name')->where(fn ($query) => $query->where('sede_id', $request->integer('sede_id'))),
            ],
            'code' => ['nullable', 'string', 'max:30', 'unique:operational_areas,code'],
            'is_active' => ['required', 'boolean'],
        ]);

        OperationalArea::query()->create($validated);

        return redirect()->route('operational-areas.index')->with('success', 'Área operativa creada correctamente.');
    }

    public function update(Request $request, OperationalArea $operationalArea)
    {
        $validated = $request->validate([
            'sede_id' => ['required', 'exists:sedes,id'],
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('operational_areas', 'name')
                    ->ignore($operationalArea->id)
                    ->where(fn ($query) => $query->where('sede_id', $request->integer('sede_id'))),
            ],
            'code' => ['nullable', 'string', 'max:30', Rule::unique('operational_areas', 'code')->ignore($operationalArea->id)],
            'is_active' => ['required', 'boolean'],
        ]);

        $operationalArea->update($validated);

        return redirect()->route('operational-areas.index')->with('success', 'Área operativa actualizada correctamente.');
    }
}
