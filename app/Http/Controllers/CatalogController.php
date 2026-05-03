<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Profile;
use App\Models\Test;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogController extends Controller
{
    public function index()
    {
        $areaNames = Area::query()
            ->select('name')
            ->orderBy('name')
            ->pluck('name')
            ->values();

        $profileNames = Profile::query()
            ->select('name')
            ->orderBy('name')
            ->pluck('name')
            ->values();

        return view('catalog.index', compact('areaNames', 'profileNames'));
    }

    public function list()
    {
        $areas = Area::with('tests')->latest()->get();
        $profiles = Profile::with('tests')->latest()->get();

        return view('catalog.list', compact('areas', 'profiles'));
    }

    public function editProfile(Profile $profile)
    {
        $tests = Test::with('area')->orderBy('name')->get();

        return view('catalog.edit-profile', [
            'profile' => $profile->load('tests'),
            'tests' => $tests,
            'selectedTestIds' => $profile->tests->pluck('id')->all(),
        ]);
    }

    public function updateProfile(Request $request, Profile $profile): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'test_ids' => ['nullable', 'array'],
            'test_ids.*' => ['integer', 'exists:tests,id'],
        ]);

        $profile->update(['name' => $validated['name']]);
        $profile->tests()->sync($validated['test_ids'] ?? []);

        return redirect()->route('catalog.list')->with('success', 'Perfil actualizado correctamente.');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'area_name' => ['required', 'string', 'max:255'],
            'profile_name' => ['nullable', 'string', 'max:255'],
            'tests' => ['required', 'array', 'min:1'],
            'tests.*.name' => ['required', 'string', 'max:255'],
            'tests.*.unit' => ['nullable', 'string', 'max:100'],
            'tests.*.reference_value' => ['nullable', 'string', 'max:255'],
            'tests.*.type' => ['required', 'in:number,text,select'],
            'tests.*.options' => ['nullable', 'array'],
            'tests.*.options.*.label' => ['required_with:tests.*.options.*.value', 'nullable', 'string', 'max:255'],
            'tests.*.options.*.value' => ['required_with:tests.*.options.*.label', 'nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated): void {
            $area = Area::firstOrCreate(['name' => $validated['area_name']]);

            $createdTests = [];

            foreach ($validated['tests'] as $index => $testData) {
                $test = $area->tests()->create([
                    'name' => $testData['name'],
                    'unit' => $testData['unit'] ?? null,
                    'reference_value' => $testData['reference_value'] ?? null,
                    'type' => $testData['type'],
                ]);

                if ($testData['type'] === 'select' && !empty($testData['options'])) {
                    $options = collect($testData['options'])
                        ->filter(fn ($option) => filled($option['label'] ?? null) && filled($option['value'] ?? null))
                        ->map(fn ($option) => [
                            'label' => $option['label'],
                            'value' => $option['value'],
                        ])->values()->all();

                    if (!empty($options)) {
                        $test->options()->createMany($options);
                    }
                }

                $createdTests[$index] = $test->id;
            }

            if (filled($validated['profile_name'] ?? null)) {
                $profile = Profile::firstOrCreate(['name' => $validated['profile_name']]);
                $profile->tests()->sync(array_values($createdTests));
            }
        });

        return back()->with('success', 'Catálogo registrado correctamente.');
    }
}
