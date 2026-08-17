<?php

namespace Tests\Feature;

use App\Models\Fua;
use App\Models\FuaConfiguration;
use App\Models\NephrologyConsultation;
use App\Models\Order;
use App\Models\Patient;
use App\Models\User;
use App\Services\FuaNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuaNumberingAndOrderEditingTest extends TestCase
{
    use RefreshDatabase;

    public function test_hemodialysis_and_nephrology_use_one_consecutive_sequence(): void
    {
        $configuration = FuaConfiguration::global();
        $configuration->update([
            'hemodialysis_series' => 'SERIE',
            'hemodialysis_next_number' => 20,
            'nephrology_series' => 'ANTIGUA',
            'nephrology_next_number' => 90,
            'number_length' => 4,
        ]);

        $patient = Patient::factory()->create();
        $hemodialysis = $this->order($patient, Fua::HEMODIALYSIS, 'HD-1');
        $nephrology = $this->order($patient, Fua::NEPHROLOGY, 'NEFRO-1');
        $service = app(FuaNumberService::class);

        $this->assertSame('SERIE-0020', $service->createForOrder($hemodialysis)->number);
        $this->assertSame('SERIE-0021', $service->createForOrder($nephrology)->number);
        $this->assertSame(22, FuaConfiguration::global()->hemodialysis_next_number);
        $this->assertSame(22, FuaConfiguration::global()->nephrology_next_number);
    }

    public function test_generated_nephrology_order_can_be_edited_and_updates_its_consultation_date(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();
        $order = $this->order($patient, Fua::NEPHROLOGY, 'NEFRO-2');
        $consultation = NephrologyConsultation::create([
            'order_id' => $order->id,
            'patient_id' => $patient->id,
            'sede_id' => $patient->sede_id,
            'consultation_date' => '2026-08-16',
        ]);

        $response = $this->actingAs($user)->withoutMiddleware()->put(route('orders.update', $order), [
            'sala' => 'CONSULTA NEFROLÓGICA',
            'turno' => 'N/A',
            'horas_dialisis' => 0.5,
            'fecha_orden' => '2026-08-20',
        ]);

        $response->assertRedirect(route('orders.index'));
        $this->assertSame('2026-08-20', $order->fresh()->fecha_orden);
        $this->assertSame('2026-08-20', $consultation->fresh()->consultation_date->format('Y-m-d'));
    }

    public function test_non_signature_reason_is_shown_on_hemodialysis_and_nephrology_fuas(): void
    {
        $patient = Patient::factory()->create([
            'fua_non_signature_reason' => 'Presenta dificultad motora para firmar',
        ]);

        foreach ([Fua::HEMODIALYSIS, Fua::NEPHROLOGY] as $index => $type) {
            $order = $this->order($patient, $type, 'FUA-HUELLA-'.$index);
            $fua = app(FuaNumberService::class)->createForOrder($order);
            $fua->load('order.patient');

            $document = view('fuas.pdf', [
                'fua' => $fua,
                'responsible' => null,
                'configuration' => FuaConfiguration::global(),
                'medications' => [],
                'procedures' => [],
                'logoData' => null,
            ])->render();

            $this->assertStringContainsString('MOTIVO DE NO FIRMA DE FUA:', $document);
            $this->assertStringContainsString('Presenta dificultad motora para firmar', $document);
            $this->assertStringContainsString('PACIENTE COLOCA SU HUELLA EN SEÑAL DE CONFORMIDAD DE LA ATENCIÓN.', $document);
        }
    }

    public function test_non_signature_message_is_hidden_when_patient_has_no_reason(): void
    {
        $patient = Patient::factory()->create([
            'fua_non_signature_reason' => null,
        ]);
        $order = $this->order($patient, Fua::HEMODIALYSIS, 'FUA-SIN-HUELLA');
        $fua = app(FuaNumberService::class)->createForOrder($order);
        $fua->load('order.patient');

        $document = view('fuas.pdf', [
            'fua' => $fua,
            'responsible' => null,
            'configuration' => FuaConfiguration::global(),
            'medications' => [],
            'procedures' => [],
            'logoData' => null,
        ])->render();

        $this->assertStringNotContainsString('MOTIVO DE NO FIRMA DE FUA:', $document);
        $this->assertStringNotContainsString('PACIENTE COLOCA SU HUELLA EN SEÑAL DE CONFORMIDAD DE LA ATENCIÓN.', $document);
    }

    public function test_fua_print_views_can_filter_by_module_and_shift(): void
    {
        $user = User::factory()->create();
        $matchingPatient = Patient::factory()->create(['modulo' => '2']);
        $otherPatient = Patient::factory()->create(['modulo' => '1']);

        foreach ([Fua::HEMODIALYSIS, Fua::NEPHROLOGY] as $index => $type) {
            $matchingOrder = $this->order($matchingPatient, $type, 'MATCH-'.$index);
            $matchingOrder->update(['sala' => $type === Fua::NEPHROLOGY ? 'CONSULTA NEFROLÓGICA' : 'MODULO 2', 'turno' => '3']);
            $matchingFua = app(FuaNumberService::class)->createForOrder($matchingOrder);

            $otherOrder = $this->order($otherPatient, $type, 'OTHER-'.$index);
            $otherOrder->update(['turno' => '1']);
            $otherFua = app(FuaNumberService::class)->createForOrder($otherOrder);

            $route = $type === Fua::NEPHROLOGY ? 'fuas.nephrology.index' : 'fuas.hemodialysis.index';
            $response = $this->actingAs($user)->withoutMiddleware()->get(route($route, [
                'all_dates' => 1,
                'modulo' => 2,
                'turno' => 3,
            ]));

            $response->assertOk()
                ->assertSee($matchingFua->number)
                ->assertDontSee($otherFua->number)
                ->assertSee('value="2" selected', false)
                ->assertSee('value="3" selected', false);
        }
    }

    private function order(Patient $patient, string $type, string $code): Order
    {
        return Order::create([
            'patient_id' => $patient->id,
            'sede_id' => $patient->sede_id,
            'codigo_unico' => $code,
            'sala' => $type === Fua::NEPHROLOGY ? 'CONSULTA NEFROLÓGICA' : 'MODULO 1',
            'turno' => '1',
            'attention_type' => $type,
            'laboratory_period' => $type === Fua::HEMODIALYSIS ? 'M' : null,
            'horas_dialisis' => 0.5,
            'fecha_orden' => '2026-08-16',
        ]);
    }
}
