<?php

namespace App\Http\Controllers;

use App\Models\OperationalArea;
use App\Models\Sede;
use App\Support\CurrentSede;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OperationalAreaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:users.view')->only(['index']);
        $this->middleware('permission:users.edit')->only(['store', 'update']);
    }

    public function index()
    {
        $currentSedeId = CurrentSede::id();
        $isSuperAdmin = auth()->user()?->hasRole('superadmin');

        $areas = OperationalArea::query()
            ->with(['sede', 'users:id,name'])
            ->when(! $isSuperAdmin && $currentSedeId, function ($query) use ($currentSedeId) {
                $query->where('sede_id', $currentSedeId);
            })
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $sedes = Sede::query()
            ->where('is_active', true)
            ->when(! $isSuperAdmin && $currentSedeId, function ($query) use ($currentSedeId) {
                $query->whereKey($currentSedeId);
            })
            ->orderBy('name')
            ->get();

        return view('operational-areas.index', compact('areas', 'sedes'));
    }

    public function store(Request $request)
    {
        $this->ensureCanManageSede($request->integer('sede_id'));

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
        $this->ensureCanManageSede($operationalArea->sede_id);
        $this->ensureCanManageSede($request->integer('sede_id'));

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

    private function ensureCanManageSede(?int $sedeId): void
    {
        $user = auth()->user();

        if (! $user || $user->hasRole('superadmin')) {
            return;
        }

        if (! $sedeId || $sedeId !== CurrentSede::id()) {
            throw ValidationException::withMessages([
                'sede_id' => 'Solo puede gestionar áreas operativas de su sede activa.',
            ]);
        }
    }
}
