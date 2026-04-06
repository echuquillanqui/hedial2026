<?php

namespace App\Http\Controllers;

use App\Models\Sede;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SedeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:users.view')->only(['index']);
        $this->middleware('permission:users.create')->only(['store']);
        $this->middleware('permission:users.edit')->only(['update']);
    }

    public function index()
    {
        $sedes = Sede::orderByDesc('is_principal')->orderBy('name')->get();

        return view('sedes.index', compact('sedes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:sedes,name'],
            'code' => ['nullable', 'string', 'max:30', 'unique:sedes,code'],
            'is_active' => ['required', 'boolean'],
            'is_principal' => ['required', 'boolean'],
        ]);

        if (! (bool) $validated['is_principal'] && ! Sede::query()->where('is_principal', true)->exists()) {
            $validated['is_principal'] = true;
        }

        DB::transaction(function () use ($validated) {
            if ((bool) $validated['is_principal']) {
                Sede::query()->update(['is_principal' => false]);
                Warehouse::query()->update(['is_principal' => false]);
            }

            $sede = Sede::create($validated);

            $warehouse = Warehouse::query()->firstOrCreate(
                ['sede_id' => $sede->id],
                ['name' => 'Almacén ' . $sede->name, 'is_principal' => false, 'is_active' => true]
            );

            if ((bool) $sede->is_principal) {
                $warehouse->update(['is_principal' => true]);
            }
        });

        return redirect()->route('sedes.index')->with('success', 'Sede creada correctamente.');
    }

    public function update(Request $request, Sede $sede)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('sedes', 'name')->ignore($sede->id)],
            'code' => ['nullable', 'string', 'max:30', Rule::unique('sedes', 'code')->ignore($sede->id)],
            'is_active' => ['required', 'boolean'],
            'is_principal' => ['required', 'boolean'],
        ]);

        if (! (bool) $validated['is_principal'] && $sede->is_principal && ! Sede::query()->where('id', '!=', $sede->id)->where('is_principal', true)->exists()) {
            return redirect()->route('sedes.index')->withErrors([
                'is_principal' => 'Debe existir al menos una sede principal activa.',
            ]);
        }

        DB::transaction(function () use ($validated, $sede) {
            if ((bool) $validated['is_principal']) {
                Sede::query()->where('id', '!=', $sede->id)->update(['is_principal' => false]);
                Warehouse::query()->update(['is_principal' => false]);
            }

            $sede->update($validated);

            $warehouse = Warehouse::query()->firstOrCreate(
                ['sede_id' => $sede->id],
                ['name' => 'Almacén ' . $sede->name, 'is_principal' => false, 'is_active' => true]
            );

            $warehouse->update([
                'name' => 'Almacén ' . $sede->name,
                'is_principal' => (bool) $sede->is_principal,
            ]);
        });

        return redirect()->route('sedes.index')->with('success', 'Sede actualizada correctamente.');
    }
}
