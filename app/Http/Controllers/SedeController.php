<?php

namespace App\Http\Controllers;

use App\Models\Sede;
use Illuminate\Http\Request;
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
        $sedes = Sede::orderBy('name')->get();

        return view('sedes.index', compact('sedes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:sedes,name'],
            'code' => ['nullable', 'string', 'max:30', 'unique:sedes,code'],
            'is_active' => ['required', 'boolean'],
        ]);

        Sede::create($validated);

        return redirect()->route('sedes.index')->with('success', 'Sede creada correctamente.');
    }

    public function update(Request $request, Sede $sede)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('sedes', 'name')->ignore($sede->id)],
            'code' => ['nullable', 'string', 'max:30', Rule::unique('sedes', 'code')->ignore($sede->id)],
            'is_active' => ['required', 'boolean'],
        ]);

        $sede->update($validated);

        return redirect()->route('sedes.index')->with('success', 'Sede actualizada correctamente.');
    }
}
