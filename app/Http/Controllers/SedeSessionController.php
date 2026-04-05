<?php

namespace App\Http\Controllers;

use App\Models\Sede;
use App\Support\CurrentSede;
use Illuminate\Http\Request;

class SedeSessionController extends Controller
{
    public function select()
    {
        $user = auth()->user();
        $sedes = $user->sedes()->where('is_active', true)->orderBy('name')->get();

        if ($sedes->isEmpty()) {
            return redirect()->route('home')->with('error', 'Su usuario no tiene sedes asignadas.');
        }

        if ($sedes->count() === 1) {
            CurrentSede::set($sedes->first());
            return redirect()->route('home');
        }

        return view('auth.select-sede', compact('sedes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sede_id' => 'required|exists:sedes,id',
        ]);

        $sede = auth()->user()->sedes()->whereKey($request->integer('sede_id'))->first();

        if (! $sede instanceof Sede) {
            return back()->withErrors(['sede_id' => 'La sede seleccionada no está asignada a su usuario.']);
        }

        CurrentSede::set($sede);

        return redirect()->intended(route('home'));
    }
}
