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
        $tests = Test::query()
            ->with('area')
            ->where('is_fissal', true)
            ->orderBy('area_id')
            ->orderBy('id')
            ->get();

        $areaNames = Area::query()
            ->select('name')
            ->orderBy('name')
            ->pluck('name')
            ->values();

        return view('catalog.index', compact('tests', 'areaNames'));
    }

    public function update(Request $request): RedirectResponse
    {
        $fissalTests = Test::query()->where('is_fissal', true)->get()->keyBy('id');

        $validated = $request->validate([
            'tests' => ['required', 'array', 'size:' . $fissalTests->count()],
            'tests.*.area' => ['required', 'string', 'max:255'],
            'tests.*.name' => ['required', 'string', 'max:255'],
            'tests.*.unit' => ['nullable', 'string', 'max:100'],
            'tests.*.reference_value' => ['nullable', 'string', 'max:255'],
            'tests.*.type' => ['required', 'in:number,text,select'],
            'tests.*.frequency' => ['required', 'in:M,B,T,S'],
            'tests.*.code' => ['nullable', 'string', 'max:30', 'distinct'],
            'tests.*.fua_quantity' => ['required', 'integer', 'min:1'],
        ]);

        $submittedIds = collect(array_keys($validated['tests']))->map(fn ($id) => (int) $id)->sort()->values();
        $expectedIds = $fissalTests->keys()->map(fn ($id) => (int) $id)->sort()->values();

        abort_unless($submittedIds->all() === $expectedIds->all(), 422, 'El catálogo solo puede incluir los exámenes FISSAL.');

        DB::transaction(function () use ($validated, $fissalTests): void {
            foreach ($validated['tests'] as $id => $data) {
                $area = Area::firstOrCreate(['name' => trim($data['area'])]);
                $fissalTests->get((int) $id)->update([
                    'area_id' => $area->id,
                    'name' => $data['name'],
                    'unit' => $data['unit'] ?? null,
                    'reference_value' => $data['reference_value'] ?? null,
                    'type' => $data['type'],
                    'frequency' => $data['frequency'],
                    'code' => $data['code'] ?? null,
                    'fua_quantity' => $data['fua_quantity'],
                ]);
            }
        });

        return redirect()->route('catalog.index')->with('success', 'Catálogo actualizado correctamente.');
    }

    public function list()
    {
        $areas = Area::with('tests')->latest()->get();
        $profiles = Profile::with('tests')->latest()->paginate(10);

        return view('catalog.list', compact('areas', 'profiles'));
    }

    public function editArea(Area $area)
    {
        return view('catalog.edit-area', [
            'area' => $area,
        ]);
    }

    public function updateArea(Request $request, Area $area): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $area->update($validated);

        return redirect()->route('catalog.list')->with('success', 'Área de laboratorio actualizada correctamente.');
    }

    public function destroyProfile(Profile $profile): RedirectResponse
    {
        DB::transaction(function () use ($profile): void {
            $profile->tests()->detach();
            $profile->delete();
        });

        return redirect()->route('catalog.list')->with('success', 'Perfil eliminado correctamente.');
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
            'tests.*.code' => ['nullable', 'string', 'max:30', 'distinct'],
            'tests.*.fua_quantity' => ['nullable', 'integer', 'min:1'],
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
                    'code' => $testData['code'] ?? null,
                    'fua_quantity' => $testData['fua_quantity'] ?? 1,
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
