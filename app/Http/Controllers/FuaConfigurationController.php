<?php

namespace App\Http\Controllers;

use App\Models\FuaConfiguration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'dialysis_equipment' => ['required', 'string', 'max:255'],
            'diagnosis_code' => ['required', 'string', 'max:20'],
            'diagnosis_name' => ['required', 'string', 'max:255'],
            'consultation_reason' => ['required', 'string', 'max:255'],
            'default_anamnesis' => ['nullable', 'string', 'max:1000'],
            'default_etiology' => ['nullable', 'string', 'max:255'],
            'default_vascular_access' => ['nullable', 'string', 'max:255'],
            'secondary_diagnosis_code' => ['nullable', 'string', 'max:20'],
            'secondary_diagnosis_name' => ['nullable', 'string', 'max:255'],
            'responsible_name' => ['nullable', 'string', 'max:255'],
            'responsible_document' => ['nullable', 'string', 'max:30'],
            'responsible_college_number' => ['nullable', 'string', 'max:30'],
            'responsible_specialty' => ['required', 'string', 'max:100'],
            'hemodialysis_series' => ['required', 'string', 'max:30'],
            'hemodialysis_next_number' => ['required', 'integer', 'min:1'],
            'correction_series' => ['required', 'string', 'max:30'],
            'correction_next_number' => ['required', 'integer', 'min:1'],
            'number_length' => ['required', 'integer', 'between:1,12'],
        ]);

        $configuration = FuaConfiguration::global();

        if ($request->boolean('remove_logo') || $request->hasFile('logo')) {
            if ($configuration->logo_path) {
                Storage::disk('public')->delete($configuration->logo_path);
            }
            $data['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('fua-branding', 'public');
        }

        unset($data['logo'], $data['remove_logo']);
        DB::transaction(function () use ($configuration, $data) {
            // Se mantienen las columnas históricas sincronizadas para que las
            // instalaciones existentes puedan actualizarse sin perder datos.
            $data['nephrology_series'] = $data['hemodialysis_series'];
            $data['nephrology_next_number'] = $data['hemodialysis_next_number'];
            $configuration->update($data);
        });

        return back()->with('success', 'Configuración global de FUA actualizada.');
    }
}
