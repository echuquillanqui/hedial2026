<?php

namespace App\Http\Controllers;

use App\Models\Fua;
use App\Models\FuaConfiguration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class FuaController extends Controller
{
    public function index(Request $request)
    {
        $fuas = Fua::with(['order.patient', 'order.sede'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(function ($query) use ($search) {
                    $query->where('number', 'like', "%{$search}%")
                        ->orWhereHas('order.patient', fn ($patient) => $patient
                            ->where('dni', 'like', "%{$search}%")
                            ->orWhere('surname', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->type))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('fuas.index', compact('fuas'));
    }

    public function preview(Fua $fua)
    {
        $fua->load(['order.patient', 'order.sede']);

        return view('fuas.preview', compact('fua'));
    }

    public function pdf(Request $request, Fua $fua)
    {
        $fua->load(['order.patient', 'order.sede']);
        $document = Pdf::loadView('fuas.pdf', [
            'fua' => $fua,
            'configuration' => FuaConfiguration::global(),
        ])->setPaper('a4');
        $filename = 'fua-'.str_replace(['/', '\\'], '-', $fua->number).'.pdf';

        return $request->boolean('download')
            ? $document->download($filename)
            : $document->stream($filename);
    }
}
