<?php

namespace App\Http\Controllers;

use App\Models\ExtraMaterial;
use App\Models\HemodialysisMaterial;
use App\Models\HemodialysisMaterialConsumption;
use App\Models\Order;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Support\CurrentSede;

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
            echo '<tr><td>Atenciones pendientes</td><td>'.$escape($dashboard['kpis']['sesionesPendientes']).'</td></tr>';
            echo '<tr><td>Total heparina aplicada</td><td>'.$escape($dashboard['kpis']['totalHeparina']).'</td></tr>';
            echo '<tr><td>Total dializadores registrados</td><td>'.$escape($dashboard['kpis']['totalDializadoresRegistrados']).'</td></tr>';
            echo '<tr><td>Materiales de diálisis consumidos</td><td>'.$escape($dashboard['kpis']['materialesDialisisConsumidos']).'</td></tr>';
            echo '<tr><td>Materiales indirectos consumidos</td><td>'.$escape($dashboard['kpis']['materialesIndirectosConsumidos']).'</td></tr>';
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
            echo '<tr><th colspan="2">Materiales base de diálisis consumidos</th></tr>';
            echo '<tr><th>Material</th><th>Cantidad</th></tr>';
            foreach ($dashboard['materialesDialisis'] as $material) {
                echo '<tr><td>'.$escape($material['nombre']).'</td><td>'.$escape($material['cantidad']).'</td></tr>';
            }
            echo '</table><br>';

            echo '<table border="1">';
            echo '<tr><th colspan="2">Materiales indirectos consumidos</th></tr>';
            echo '<tr><th>Material</th><th>Cantidad</th></tr>';
            foreach ($dashboard['materialesIndirectos'] as $material) {
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
            ->when(CurrentSede::id(), fn ($q) => $q->where('sede_id', CurrentSede::id()))
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
            $heparina = $this->normalizeDose($medical->heparina ?? null);
            $epo = $this->normalizeDose($medical->epo2000 ?? null)
                + $this->normalizeDose($medical->epo4000 ?? null)
                + $this->normalizeDose($nurse->epo2000 ?? null)
                + $this->normalizeDose($nurse->epo4000 ?? null);
            $hierro = $this->normalizeDose($medical->hierro ?? null) + $this->normalizeDose($nurse->hierro ?? null);
            $vitaminaB12 = $this->normalizeDose($medical->vitamina_b12 ?? null) + $this->normalizeDose($nurse->vitamina_b12 ?? null);
            $calcitriol = $this->normalizeDose($medical->calcitriol ?? null) + $this->normalizeDose($nurse->calcitriol ?? null);
            $bicarbonato = $this->normalizeDose($medical->bicarbonato ?? null);
            $qb = $this->normalizeDose($medical->qb ?? null);

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
                'bicarbonato' => $bicarbonato,
                'qb' => $qb,
                'estado' => $completa ? 'Completa' : 'Pendiente',
                'completa' => $completa,
            ];
        });

        $materialesDialisis = HemodialysisMaterialConsumption::query()
            ->when(CurrentSede::id(), fn ($q) => $q->whereHas('order', fn ($oq) => $oq->where('sede_id', CurrentSede::id())))
            ->with('material:id,name,unit')
            ->whereDate('consumed_at', $fecha)
            ->get()
            ->groupBy('hemodialysis_material_id')
            ->map(function ($consumos) {
                $material = $consumos->first()->material;
                $cantidad = round((float) $consumos->sum('quantity'), 2);
                $unidad = $material?->unit ? ' '.$material->unit : '';

                return [
                    'nombre' => $material?->name ?? 'Material base',
                    'cantidad' => rtrim(rtrim(number_format($cantidad, 2, '.', ''), '0'), '.').$unidad,
                    'cantidad_numerica' => $cantidad,
                ];
            })
            ->values();

        $nombresMaterialesBase = HemodialysisMaterial::query()
            ->pluck('name')
            ->map(fn ($name) => mb_strtolower(trim((string) $name)))
            ->flip();

        $materialesIndirectos = ExtraMaterial::query()
            ->when(CurrentSede::id(), fn ($q) => $q->whereHas('order', fn ($oq) => $oq->where('sede_id', CurrentSede::id())))
            ->whereDate('usage_date', $fecha)
            ->get(['material_name', 'quantity'])
            ->filter(function ($material) use ($nombresMaterialesBase) {
                $nombreNormalizado = mb_strtolower(trim((string) $material->material_name));

                return ! $nombresMaterialesBase->has($nombreNormalizado);
            })
            ->groupBy('material_name')
            ->map(function ($materiales, $nombre) {
                $cantidad = round((float) $materiales->sum('quantity'), 2);

                return [
                    'nombre' => $nombre,
                    'cantidad' => rtrim(rtrim(number_format($cantidad, 2, '.', ''), '0'), '.'),
                    'cantidad_numerica' => $cantidad,
                ];
            })
            ->values();

        $completas = $resumenInsumos->where('completa', true)->count();

        return [
            'fechaActual' => $fecha,
            'ordenesCompletas' => $ordenes,
            'ordenesPendientes' => $resumenInsumos->where('completa', false),
            'total' => $ordenes->count(),
            'completos' => $completas,
            'resumenInsumos' => $resumenInsumos,
            'materialesDialisis' => $materialesDialisis,
            'materialesIndirectos' => $materialesIndirectos,
            'kpis' => [
                'totalSesiones' => $ordenes->count(),
                'sesionesCompletas' => $completas,
                'sesionesPendientes' => $resumenInsumos->where('completa', false)->count(),
                'totalHeparina' => $resumenInsumos->sum('heparina'),
                'totalDializadoresRegistrados' => $resumenInsumos->sum('dializador'),
                'materialesDialisisConsumidos' => $materialesDialisis->sum('cantidad_numerica'),
                'materialesIndirectosConsumidos' => $materialesIndirectos->sum('cantidad_numerica'),
            ],
        ];
    }


    private function normalizeDose($value): float|int
    {
        if ($value === null) {
            return 0;
        }

        if (is_string($value)) {
            $clean = trim(str_replace(',', '.', $value));
            if ($clean == '') {
                return 0;
            }

            return is_numeric($clean) ? (0 + $clean) : 0;
        }

        return is_numeric($value) ? (0 + $value) : 0;
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
