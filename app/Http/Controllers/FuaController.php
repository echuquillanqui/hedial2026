<?php

namespace App\Http\Controllers;

use App\Models\Fua;
use App\Models\FuaConfiguration;
use App\Models\User;
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
        $fua->load(['order.patient', 'order.sede', 'order.medical.usuarioInicia', 'responsibleUser']);
        $doctors = User::query()
            ->where(function ($query) {
                $query->whereHas('roles', fn ($roles) => $roles->where('name', 'medico'))
                    ->orWhere('profession', 'like', '%medic%')
                    ->orWhere('profession', 'like', '%médic%')
                    ->orWhere('profession', 'like', '%nefro%');
            })
            ->orderBy('name')
            ->get();

        return view('fuas.preview', compact('fua', 'doctors'));
    }

    public function updateResponsible(Request $request, Fua $fua)
    {
        $data = $request->validate([
            'responsible_user_id' => ['nullable', 'exists:users,id'],
        ]);

        if (! empty($data['responsible_user_id'])) {
            $doctor = User::findOrFail($data['responsible_user_id']);
            $profession = mb_strtolower($doctor->profession ?? '');
            abort_unless($doctor->hasRole('medico') || str_contains($profession, 'medic') || str_contains($profession, 'médic') || str_contains($profession, 'nefro'), 422);
        }

        $fua->update($data);

        return back()->with('success', 'Médico responsable actualizado en la FUA.');
    }

    public function pdf(Request $request, Fua $fua)
    {
        $fua->load([
            'order.patient', 'order.sede', 'order.medical.usuarioInicia',
            'order.laboratoryOrder.items.test', 'responsibleUser',
        ]);
        $responsible = $fua->responsibleUser ?: $fua->order?->medical?->usuarioInicia;
        $procedures = $this->procedures($fua);
        $configuration = FuaConfiguration::global();
        $view = $fua->type === 'NEPHROLOGY' ? 'fuas.pdf_nephrology' : 'fuas.pdf';
        $logoData = $this->logoData($configuration->logo_path);
        $document = Pdf::loadView($view, [
            'fua' => $fua,
            'configuration' => $configuration,
            'logoData' => $logoData,
            'responsible' => $responsible,
            'procedures' => $procedures,
        ])->setPaper('a4');
        $filename = 'fua-'.str_replace(['/', '\\'], '-', $fua->number).'.pdf';

        return $request->boolean('download')
            ? $document->download($filename)
            : $document->stream($filename);
    }

    private function logoData(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $absolutePath = storage_path('app/public/'.$path);
        if (! is_file($absolutePath)) {
            return null;
        }

        $mime = mime_content_type($absolutePath) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($absolutePath));
    }

    private function procedures(Fua $fua): array
    {
        $rows = [['code' => '90937', 'description' => 'Procedimiento de hemodiálisis que requiere repetida(s) evaluación(es) con o sin', 'quantity' => 1]];
        $items = $fua->order?->laboratoryOrder?->items ?? collect();
        $urea = $items->filter(fn ($item) => str_contains(mb_strtolower($item->test?->name ?? ''), 'urea'));

        if ($urea->isNotEmpty()) {
            $rows[] = ['code' => '84520', 'description' => 'Nitrógeno ureico; cuantitativo (Urea sérica)', 'quantity' => $urea->sum(fn ($item) => $item->test?->fua_quantity ?? 1)];
        }

        foreach ($items->reject(fn ($item) => str_contains(mb_strtolower($item->test?->name ?? ''), 'urea')) as $item) {
            $rows[] = [
                'code' => $item->test?->code ?? '',
                'description' => $item->test?->name ?? '',
                'quantity' => $item->test?->fua_quantity ?? 1,
            ];
        }

        return $rows;
    }
}
