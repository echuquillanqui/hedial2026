<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Profile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogController extends Controller
{
    public function index()
    {
        return view('catalog.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'area_name' => ['required', 'string', 'max:255'],
            'profile_name' => ['required', 'string', 'max:255'],
            'tests' => ['required', 'array', 'min:1'],
            'tests.*.name' => ['required', 'string', 'max:255'],
            'tests.*.unit' => ['nullable', 'string', 'max:100'],
            'tests.*.reference_value' => ['nullable', 'string', 'max:255'],
            'tests.*.type' => ['required', 'in:number,text,select'],
            'tests.*.options' => ['nullable', 'array'],
            'tests.*.options.*.label' => ['required_with:tests.*.options.*.value', 'nullable', 'string', 'max:255'],
            'tests.*.options.*.value' => ['required_with:tests.*.options.*.label', 'nullable', 'string', 'max:255'],
            'profile_tests' => ['nullable', 'array'],
            'profile_tests.*' => ['integer', 'min:0'],
        ]);

        DB::transaction(function () use ($validated): void {
            $area = Area::create(['name' => $validated['area_name']]);

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

            $profile = Profile::create(['name' => $validated['profile_name']]);

            $selectedIndexes = collect($validated['profile_tests'] ?? [])->map(fn ($i) => (int) $i);
            $selectedTestIds = $selectedIndexes
                ->map(fn ($i) => $createdTests[$i] ?? null)
                ->filter()
                ->values()
                ->all();

            $profile->tests()->sync($selectedTestIds);
        });

        return back()->with('success', 'Catálogo registrado correctamente.');
    }
}
