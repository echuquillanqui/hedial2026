<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $dashboard = $this->buildDashboardData($request->get('date', now()->format('Y-m-d')));

        return view('home', $dashboard);
    }

    public function exportPdf(Request $request)
    {
        $dashboard = $this->buildDashboardData($request->get('date', now()->format('Y-m-d')));

        $pdf = Pdf::loadView('exports.dashboard_pdf', $dashboard)->setPaper('a4', 'landscape');

        return $pdf->download('dashboard-hemodialisis-'.$dashboard['fechaActual'].'.pdf');
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $dashboard = $this->buildDashboardData($request->get('date', now()->format('Y-m-d')));
        $filename = 'dashboard-hemodialisis-'.$dashboard['fechaActual'].'.csv';

        return response()->streamDownload(function () use ($dashboard) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Dashboard de Hemodiálisis', $dashboard['fechaActual']]);
            fputcsv($handle, []);
            fputcsv($handle, ['Totales']);
            fputcsv($handle, ['Total sesiones', $dashboard['kpis']['totalSesiones']]);
            fputcsv($handle, ['Sesiones completas', $dashboard['kpis']['sesionesCompletas']]);
            fputcsv($handle, ['Sesiones pendientes', $dashboard['kpis']['sesionesPendientes']]);
            fputcsv($handle, ['Total heparina aplicada', $dashboard['kpis']['totalHeparina']]);
            fputcsv($handle, ['Total dializadores registrados', $dashboard['kpis']['totalDializadoresRegistrados']]);
            fputcsv($handle, ['Materiales no registrados (estimado por sesión)', $dashboard['kpis']['noRegistradosEstimados']]);
            fputcsv($handle, []);

            fputcsv($handle, ['Consumo por sesión']);
            fputcsv($handle, [
                'Paciente',
                'Código',
                'Módulo',
                'Turno',
                'Dializador',
                'Heparina',
                'EPO',
                'Hierro',
                'Vitamina B12',
                'Calcitriol',
                'Bicarbonato',
                'QB',
                'Estado ficha',
            ]);

            foreach ($dashboard['resumenInsumos'] as $fila) {
                fputcsv($handle, [
                    $fila['paciente'],
                    $fila['codigo'],
                    $fila['modulo'],
                    $fila['turno'],
                    $fila['dializador'],
                    $fila['heparina'],
                    $fila['epo'],
                    $fila['hierro'],
                    $fila['vitamina_b12'],
                    $fila['calcitriol'],
                    $fila['bicarbonato'],
                    $fila['qb'],
                    $fila['estado'],
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Materiales críticos no registrados automáticamente']);
            fputcsv($handle, ['Material', 'Cantidad estimada']);

            foreach ($dashboard['materialesNoRegistrados'] as $material) {
                fputcsv($handle, [$material['nombre'], $material['cantidad']]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function buildDashboardData(string $fecha): array
    {
        $ordenes = Order::with(['patient', 'medical', 'nurse', 'treatments'])
            ->whereDate('fecha_orden', $fecha)
            ->orderBy('turno')
            ->orderBy('sala')
            ->get();

        $resumenInsumos = $ordenes->map(function ($orden) {
            $medical = $orden->medical;
            $nurse = $orden->nurse;

            $medicalOk = $medical && ! empty($medical->pa_inicial);
            $nurseOk = $nurse && ! empty($nurse->puesto);
            $treatmentOk = $orden->treatments->count() > 0;
            $completa = $medicalOk && $nurseOk && $treatmentOk;

            $epo = (int) ($medical->epo2000 ?? 0) + (int) ($medical->epo4000 ?? 0);

            return [
                'paciente' => trim(($orden->patient->surname ?? '').' '.($orden->patient->first_name ?? '')),
                'codigo' => $orden->codigo_unico,
                'modulo' => $orden->sala,
                'turno' => $orden->turno,
                'dializador' => ($nurse && ! empty($nurse->filtro) && strtoupper($nurse->filtro) !== 'NO') ? 1 : 0,
                'heparina' => (int) ($medical->heparina ?? 0),
                'epo' => $epo,
                'hierro' => (int) ($medical->hierro ?? 0),
                'vitamina_b12' => (int) ($medical->vitamina_b12 ?? 0),
                'calcitriol' => (int) ($medical->calcitriol ?? 0),
                'bicarbonato' => ! empty($medical->bicarbonato) ? 1 : 0,
                'qb' => ! empty($medical->qb) ? 1 : 0,
                'estado' => $completa ? 'Completa' : 'Pendiente',
                'completa' => $completa,
            ];
        });

        $materialesBase = [
            'Líneas de sangre (circuito extracorpóreo)',
            'Líquido de diálisis (ácido + bicarbonato)',
            'Agujas de fístula o kit de conexión para catéter',
            'Suero fisiológico (purgado y retorno)',
            'Jeringas y agujas de varios tamaños',
            'Gasas estériles y apósitos adhesivos',
            'Desinfectante (alcohol, clorhexidina o povidona)',
            'Guantes estériles y mascarillas',
            'Esparadrapo (cinta médica)',
        ];

        $materialesNoRegistrados = collect($materialesBase)->map(fn ($nombre) => [
            'nombre' => $nombre,
            'cantidad' => $ordenes->count(),
        ]);

        $completas = $resumenInsumos->where('completa', true)->count();

        return [
            'fechaActual' => $fecha,
            'ordenesCompletas' => $ordenes,
            'ordenesPendientes' => $resumenInsumos->where('completa', false),
            'total' => $ordenes->count(),
            'completos' => $completas,
            'resumenInsumos' => $resumenInsumos,
            'materialesNoRegistrados' => $materialesNoRegistrados,
            'kpis' => [
                'totalSesiones' => $ordenes->count(),
                'sesionesCompletas' => $completas,
                'sesionesPendientes' => $resumenInsumos->where('completa', false)->count(),
                'totalHeparina' => $resumenInsumos->sum('heparina'),
                'totalDializadoresRegistrados' => $resumenInsumos->sum('dializador'),
                'noRegistradosEstimados' => $materialesNoRegistrados->sum('cantidad'),
            ],
        ];
    }
}
