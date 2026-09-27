<?php

namespace App\Http\Controllers;

use App\Models\DialysisSupplyLot;
use App\Models\DisposableDiscard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DialysisSupplyLotController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:fua.configuration.manage');
    }

    public function index()
    {
        return view('dialysis-supply-lots.index', ['lots' => DialysisSupplyLot::orderBy('category')->orderBy('measurement')->orderByDesc('valid_from')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category' => ['required', Rule::in([DisposableDiscard::DIALYZER, DisposableDiscard::BLOOD_LINES])],
            'measurement' => ['nullable', 'required_if:category,'.DisposableDiscard::DIALYZER, 'numeric', Rule::in(['1.3', '1.5', '1.8', '1.9', '2.1', '2.2'])],
            'lot_number' => ['required', 'string', 'max:80'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:valid_from'],
        ]);
        if ($data['category'] === DisposableDiscard::BLOOD_LINES) {
            $data['measurement'] = null;
        }
        DialysisSupplyLot::create($data + ['is_active' => true]);

        return back()->with('success', 'Lote global registrado correctamente.');
    }

    public function destroy(DialysisSupplyLot $dialysisSupplyLot)
    {
        $dialysisSupplyLot->update(['is_active' => false]);
        return back()->with('success', 'Lote desactivado. Las atenciones históricas conservarán su identificación por vigencia.');
    }
}
