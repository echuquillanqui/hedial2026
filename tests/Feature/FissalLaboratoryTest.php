<?php

namespace Tests\Feature;

use App\Models\LaboratoryOrder;
use App\Models\Patient;
use App\Models\Test;
use App\Models\User;
use Database\Seeders\FissalLaboratorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FissalLaboratoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_has_cumulative_fissal_frequencies(): void
    {
        $this->seed(FissalLaboratorySeeder::class);

        $this->assertSame(24, Test::where('is_fissal', true)->count());
        $this->assertEqualsCanonicalizing(['B', 'M', 'S', 'T'], Test::where('is_fissal', true)->pluck('frequency')->unique()->all());
    }

    public function test_multiple_patient_orders_can_be_generated(): void
    {
        $this->seed(FissalLaboratorySeeder::class);
        $user = User::factory()->create();
        $patients = Patient::factory()->count(2)->create();
        $testIds = Test::whereIn('frequency', ['M', 'B'])->pluck('id')->all();

        $response = $this->actingAs($user)->withoutMiddleware()->post(route('laboratory.orders.store'), [
            'patient_ids' => $patients->pluck('id')->all(), 'period' => 'B',
            'sampled_at' => '2026-08-14', 'test_ids' => $testIds,
        ]);

        $response->assertRedirect(route('laboratory.results.index'));
        $this->assertSame(2, LaboratoryOrder::count());
        $this->assertSame(count($testIds) * 2, LaboratoryOrder::withCount('items')->get()->sum('items_count'));
    }
}
