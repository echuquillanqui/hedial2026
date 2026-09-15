<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DailyNursingAnnexService
{
    private string $currentShift = '1';

    public const ROWS = [
        'iron_ev' => ['Administración de Hierro', 'E.V.'],
        'epo_ev' => ['Administración de Eritropoyetina', 'E.V.'],
        'epo_sc' => ['Administración de Eritropoyetina', 'S.C.'],
        'calcitriol_ev' => ['Administración de Calcitriol', 'E.V.'],
        'hydroxocobalamin_ev' => ['Administración de Hidroxicobalamina', 'E.V.'],
        'antibiotic_ev' => ['Administración de Antibiótico', 'E.V.'],
        'antibiotic_topical' => ['Administración de Antibiótico', 'Tópico'],
        'sample' => ['Toma de muestra', ''],
        'transfusion' => ['Transfusión sanguínea', ''],
        'access_fav' => ['Abordaje de acceso vascular', 'FAV'],
        'access_graft' => ['Abordaje de acceso vascular', 'INJERTO'],
        'access_temporary_cvc' => ['Abordaje de acceso vascular', 'CVC temporal'],
        'access_permanent_cvc' => ['Abordaje de acceso vascular', 'CVC permanente'],
        'dressing_temporary_cvc' => ['Curación de catéter venoso central', 'CVC temporal'],
        'dressing_permanent_cvc' => ['Curación de catéter venoso central', 'CVC permanente'],
        'recanulation_arterial' => ['Recanulaciones', 'Acceso arterial'],
        'recanulation_venous' => ['Recanulaciones', 'Acceso venoso'],
        'recanulation_bolus' => ['Recanulaciones', 'Total en bolo inicial'],
        'heparin_continuous' => ['Heparinización', 'Continua'],
        'heparin_free' => ['Heparinización', 'Sin heparina'],
        'heparin_restricted' => ['Heparinización', 'Restringida'],
        'vascular_test' => ['Test de acceso vascular', 'Número de test realizado al acceso vascular'],
        'oxygen_cannula' => ['Administración de oxígeno', 'Bigotera nasal'],
        'oxygen_venturi' => ['Administración de oxígeno', 'Máscara venturi'],
        'oxygen_reservoir' => ['Administración de oxígeno', 'Máscara de reservorio'],
        'hypotension' => ['Atención en complicaciones intradialíticas', 'Hipotensión'],
        'hypertension' => ['Atención en complicaciones intradialíticas', 'Hipertensión'],
        'cramps' => ['Atención en complicaciones intradialíticas', 'Calambres'],
        'nausea' => ['Atención en complicaciones intradialíticas', 'Náuseas y vómitos'],
        'headache' => ['Atención en complicaciones intradialíticas', 'Cefalea'],
        'chemical_reaction' => ['Atención en complicaciones intradialíticas', 'Reacción química'],
        'pyrogenic_reaction' => ['Atención en complicaciones intradialíticas', 'Reacción pirógena'],
        'dialyzer_change' => ['Atención en complicaciones intradialíticas', 'Cambio de dializador'],
        'venous_line_change' => ['Atención en complicaciones intradialíticas', 'Cambio de línea venosa'],
        'arterial_line_change' => ['Atención en complicaciones intradialíticas', 'Cambio de línea arterial'],
        'hyperkalemia' => ['Atención en complicaciones intradialíticas', 'Hiperkalemia'],
        'pulmonary_edema' => ['Atención en complicaciones intradialíticas', 'Edema agudo de pulmón'],
        'cardiorespiratory_arrest' => ['Atención en complicaciones intradialíticas', 'Paro cardiorrespiratorio'],
    ];

    public function calculate(Collection $orders): array
    {
        $result = collect(array_keys(self::ROWS))->mapWithKeys(fn ($key) => [$key => ['quantity' => 0, 'shifts' => array_fill_keys(['1', '2', '3', '4'], 0), 'observations' => '']])->all();
        foreach ($orders as $order) {
            $this->currentShift = in_array((string) $order->turno, ['1', '2', '3', '4'], true) ? (string) $order->turno : '1';
            $nurse = $order->nurse; $medical = $order->medical;
            $this->medication($result, 'iron_ev', $nurse?->hierro ?: $medical?->hierro);
            $this->medication($result, 'epo_sc', trim(($nurse?->epo2000 ?: $medical?->epo2000).' '.($nurse?->epo4000 ?: $medical?->epo4000)));
            $this->medication($result, 'calcitriol_ev', $nurse?->calcitriol ?: $medical?->calcitriol);
            $this->medication($result, 'hydroxocobalamin_ev', $nurse?->vitamina_b12 ?: $medical?->vitamina_b12);
            $access = Str::lower(($nurse?->acceso_arterial ?? '').' '.($nurse?->acceso_venoso ?? ''));
            $this->countIf($result, 'access_fav', Str::contains($access, 'fav'));
            $this->countIf($result, 'access_graft', Str::contains($access, ['inj', 'injerto']));
            $this->countIf($result, 'access_temporary_cvc', Str::contains($access, ['cvct', 'temporal']));
            $this->countIf($result, 'access_permanent_cvc', Str::contains($access, ['cvclp', 'permanente']));
            $heparin = Str::lower($medical?->heparina ?? '');
            $this->countIf($result, 'heparin_free', $heparin !== '' && Str::contains($heparin, ['sin', 'no ']));
            $this->countIf($result, 'heparin_restricted', Str::contains($heparin, ['restring', 'bolo']));
            $this->countIf($result, 'heparin_continuous', $heparin !== '' && ! Str::contains($heparin, ['sin', 'no ', 'restring', 'bolo']));
            $this->countIf($result, 'transfusion', $this->affirmative($nurse?->transfusions));
            $this->countIf($result, 'dressing_temporary_cvc', $this->affirmative($nurse?->dressings) && Str::contains($access, ['cvct', 'temporal']));
            $this->countIf($result, 'dressing_permanent_cvc', $this->affirmative($nurse?->dressings) && Str::contains($access, ['cvclp', 'permanente']));
            $this->countIf($result, 'oxygen_cannula', (float) ($medical?->fio2 ?? 0) > 0);
            $notes = Str::lower(collect([$medical?->problemas_clinicos, $medical?->evaluacion_final, $nurse?->observacion_final, ...$order->treatments->pluck('observacion')->all()])->filter()->join(' '));
            foreach (['hypotension'=>'hipotensi', 'hypertension'=>'hipertensi', 'cramps'=>'calambre', 'nausea'=>'náusea|nausea|vómito|vomito', 'headache'=>'cefalea', 'chemical_reaction'=>'reacción química|reaccion quimica', 'pyrogenic_reaction'=>'pirógen|pirogen', 'dialyzer_change'=>'cambio de dializador', 'venous_line_change'=>'cambio de línea venosa|cambio de linea venosa', 'arterial_line_change'=>'cambio de línea arterial|cambio de linea arterial', 'hyperkalemia'=>'hiperkal|hipercal', 'pulmonary_edema'=>'edema agudo', 'cardiorespiratory_arrest'=>'paro cardio'] as $key => $pattern) {
                if (preg_match('/'.$pattern.'/u', $notes)) {
                    $this->countIf($result, $key, true);
                    $result[$key]['observations'] = trim($result[$key]['observations'].($result[$key]['observations'] ? '; ' : '').$order->patient->full_name);
                }
            }
        }
        return $result;
    }

    private function medication(array &$result, string $key, ?string $value): void { $this->countIf($result, $key, $this->affirmative($value)); }
    private function countIf(array &$result, string $key, bool $condition): void
    {
        if ($condition) {
            $result[$key]['quantity']++;
            $result[$key]['shifts'][$this->currentShift]++;
        }
    }
    private function affirmative(?string $value): bool { return filled($value) && ! preg_match('/^(0|no|ninguno|no se realiz)/i', trim($value)); }
}
