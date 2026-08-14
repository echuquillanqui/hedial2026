<?php

namespace App\Http\Controllers;

use App\Models\FuaConfiguration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FuaConfigurationController extends Controller
{
    public function edit()
    {
        return view('fuas.configuration', ['configuration' => FuaConfiguration::global()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ipress_code' => ['required', 'string', 'max:30'],
            'ipress_name' => ['required', 'string', 'max:255'],
            'diagnosis_code' => ['required', 'string', 'max:20'],
            'diagnosis_name' => ['required', 'string', 'max:255'],
            'responsible_name' => ['nullable', 'string', 'max:255'],
            'responsible_document' => ['nullable', 'string', 'max:30'],
            'responsible_college_number' => ['nullable', 'string', 'max:30'],
            'responsible_specialty' => ['required', 'string', 'max:100'],
            'hemodialysis_series' => ['required', 'string', 'max:30'],
            'hemodialysis_next_number' => ['required', 'integer', 'min:1'],
            'nephrology_series' => ['required', 'string', 'max:30'],
            'nephrology_next_number' => ['required', 'integer', 'min:1'],
            'correction_series' => ['required', 'string', 'max:30'],
            'correction_next_number' => ['required', 'integer', 'min:1'],
            'number_length' => ['required', 'integer', 'between:1,12'],
        ]);

        FuaConfiguration::global()->update($data);

        return back()->with('success', 'Configuración global de FUA actualizada.');
    }
}
