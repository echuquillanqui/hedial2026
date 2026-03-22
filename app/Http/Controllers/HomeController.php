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
        $filename = 'dashboard-hemodialisis-'.$dashboard['fechaActual'].'.xls';

        return response()->streamDownload(function () use ($dashboard) {
            $escape = static function ($value): string {
                return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
            };

            echo '<html><head><meta charset="UTF-8"></head><body>';
            echo '<table border="1">';
            echo '<tr><th colspan="2">Dashboard de Hemodiálisis</th><td>'.$escape($dashboard['fechaActual']).'</td></tr>';
            echo '<tr><td colspan="3"></td></tr>';
            echo '<tr><th colspan="3">Totales</th></tr>';
            echo '<tr><td>Total sesiones</td><td>'.$escape($dashboard['kpis']['totalSesiones']).'</td></tr>';
            echo '<tr><td>Sesiones completas</td><td>'.$escape($dashboard['kpis']['sesionesCompletas']).'</td></tr>';
            echo '<tr><td>Sesiones pendientes</td><td>'.$escape($dashboard['kpis']['sesionesPendientes']).'</td></tr>';
            echo '<tr><td>Total heparina aplicada</td><td>'.$escape($dashboard['kpis']['totalHeparina']).'</td></tr>';
            echo '<tr><td>Total dializadores registrados</td><td>'.$escape($dashboard['kpis']['totalDializadoresRegistrados']).'</td></tr>';
            echo '<tr><td>Materiales no registrados (estimado por sesión)</td><td>'.$escape($dashboard['kpis']['noRegistradosEstimados']).'</td></tr>';
            echo '</table><br>';

            echo '<table border="1">';
            echo '<tr><th colspan="13">Consumo por sesión</th></tr>';
            echo '<tr>';
            foreach (['Paciente', 'Código', 'Módulo', 'Turno', 'Dializador', 'Heparina', 'EPO', 'Hierro', 'Vitamina B12', 'Calcitriol', 'Bicarbonato', 'QB', 'Estado ficha'] as $header) {
                echo '<th>'.$escape($header).'</th>';
            }
            echo '</tr>';

            foreach ($dashboard['resumenInsumos'] as $fila) {
                echo '<tr>';
                foreach ([
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
                ] as $value) {
                    echo '<td>'.$escape($value).'</td>';
                }
                echo '</tr>';
            }
            echo '</table><br>';

            echo '<table border="1">';
            echo '<tr><th colspan="2">Materiales críticos no registrados automáticamente</th></tr>';
            echo '<tr><th>Material</th><th>Cantidad estimada</th></tr>';
            foreach ($dashboard['materialesNoRegistrados'] as $material) {
                echo '<tr><td>'.$escape($material['nombre']).'</td><td>'.$escape($material['cantidad']).'</td></tr>';
            }
            echo '</table>';
            echo '</body></html>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
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

            $dializador = ($nurse && $this->isFieldFilled($nurse->filtro ?? null) && strtoupper((string) $nurse->filtro) !== 'NO') ? 1 : 0;
            $heparina = $this->isFieldFilled($medical->heparina ?? null) ? 1 : 0;
            $epo = ($this->isFieldFilled($medical->epo2000 ?? null) || $this->isFieldFilled($medical->epo4000 ?? null)
                || $this->isFieldFilled($nurse->epo2000 ?? null) || $this->isFieldFilled($nurse->epo4000 ?? null)) ? 1 : 0;
            $hierro = ($this->isFieldFilled($medical->hierro ?? null) || $this->isFieldFilled($nurse->hierro ?? null)) ? 1 : 0;
            $vitaminaB12 = ($this->isFieldFilled($medical->vitamina_b12 ?? null) || $this->isFieldFilled($nurse->vitamina_b12 ?? null)) ? 1 : 0;
            $calcitriol = ($this->isFieldFilled($medical->calcitriol ?? null) || $this->isFieldFilled($nurse->calcitriol ?? null)) ? 1 : 0;

            return [
                'paciente' => trim(($orden->patient->surname ?? '').' '.($orden->patient->first_name ?? '')),
                'codigo' => $orden->codigo_unico,
                'modulo' => $orden->sala,
                'turno' => $orden->turno,
                'dializador' => $dializador,
                'heparina' => $heparina,
                'epo' => $epo,
                'hierro' => $hierro,
                'vitamina_b12' => $vitaminaB12,
                'calcitriol' => $calcitriol,
                'bicarbonato' => $this->isFieldFilled($medical->bicarbonato ?? null) ? 1 : 0,
                'qb' => $this->isFieldFilled($medical->qb ?? null) ? 1 : 0,
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

    private function isFieldFilled($value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return true;
    }
}
