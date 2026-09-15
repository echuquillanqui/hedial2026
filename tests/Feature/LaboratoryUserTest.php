<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\LaboratoryOrder;
use App\Models\LaboratoryOrderItem;
use App\Models\Test;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LaboratoryUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_laboratory_role_only_receives_permissions_to_view_and_update_results(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('laboratorio');

        $this->assertEqualsCanonicalizing([
            'laboratory.results.update',
            'laboratory.results.view',
        ], $user->getAllPermissions()->pluck('name')->all());

        $this->actingAs($user)->get(route('laboratory.results.index'))->assertOk();
        $this->actingAs($user)->get(route('laboratory.orders.create'))->assertForbidden();
        $this->actingAs($user)->get(route('users.index'))->assertForbidden();
    }

    public function test_laboratory_user_can_upload_and_delete_their_digital_seal(): void
    {
        Storage::fake('public');
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('laboratorio');
        $seal = UploadedFile::fake()->image('sello.png', 240, 100);

        $this->actingAs($user)->put(route('laboratory.profile.digital-seal.update'), [
            'digital_seal' => $seal,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $path = $user->refresh()->digital_seal_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $this->actingAs($user)->delete(route('laboratory.profile.digital-seal.destroy'))
            ->assertRedirect();

        Storage::disk('public')->assertMissing($path);
        $this->assertNull($user->refresh()->digital_seal_path);
    }

    public function test_updating_results_records_the_laboratory_user_who_validated_them(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('laboratorio');
        $area = Area::create(['name' => 'Bioquímica']);
        $test = Test::create(['area_id' => $area->id, 'name' => 'Glucosa', 'type' => 'number']);
        $order = LaboratoryOrder::create(['patient_name' => 'Paciente de prueba']);
        $item = LaboratoryOrderItem::create([
            'laboratory_order_id' => $order->id,
            'test_id' => $test->id,
        ]);

        $this->actingAs($user)->put(route('laboratory.results.update', $order), [
            'results' => [$item->id => ['value' => '95', 'notes' => 'Validado']],
        ])->assertRedirect();

        $this->assertSame($user->id, $order->refresh()->validated_by_user_id);
        $this->assertSame('completed', $order->status);
        $this->assertSame('95', $item->refresh()->result_value);
    }

    public function test_administrator_can_upload_a_seal_from_the_user_form(): void
    {
        Storage::fake('public');
        $this->seed(RolesAndPermissionsSeeder::class);
        $administrator = User::factory()->create();
        $administrator->assignRole('superadmin');
        $laboratoryUser = User::factory()->create([
            'profession' => 'LABORATORIO',
            'license_number' => null,
            'specialty_number' => null,
        ]);

        $this->actingAs($administrator)->put(route('users.update', $laboratoryUser), [
            'name' => $laboratoryUser->name,
            'username' => $laboratoryUser->username,
            'email' => $laboratoryUser->email,
            'profession' => 'LABORATORIO',
            'roles' => ['laboratorio'],
            'digital_seal' => UploadedFile::fake()->image('firma-laboratorio.png', 240, 100),
        ])->assertRedirect(route('users.index'))->assertSessionHasNoErrors();

        $path = $laboratoryUser->refresh()->digital_seal_path;

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
        $this->assertTrue($laboratoryUser->hasRole('laboratorio'));
    }

    public function test_laboratory_pdf_only_shows_the_validators_seal(): void
    {
        $sealPath = 'users/digital-seals/pdf-test-seal.png';
        $absoluteSealPath = storage_path('app/public/'.$sealPath);
        File::ensureDirectoryExists(dirname($absoluteSealPath));
        File::put($absoluteSealPath, 'seal');

        try {
            $validator = User::factory()->create([
                'name' => 'Nombre que no debe mostrarse',
                'license_number' => 'COLEGIATURA-OCULTA',
                'digital_seal_path' => $sealPath,
            ]);
            $area = Area::create(['name' => 'Bioquímica']);
            $test = Test::create(['area_id' => $area->id, 'name' => 'Glucosa', 'type' => 'number']);
            $order = LaboratoryOrder::create([
                'patient_name' => 'Paciente de prueba',
                'validated_by_user_id' => $validator->id,
            ]);
            LaboratoryOrderItem::create([
                'laboratory_order_id' => $order->id,
                'test_id' => $test->id,
                'result_value' => '95',
            ]);

            $html = view('laboratory.results.pdf', [
                'orders' => collect([$order->load(['patient', 'items.test.area', 'validator'])]),
            ])->render();

            $this->assertStringContainsString('alt="Sello digital"', $html);
            $this->assertStringNotContainsString('Nombre que no debe mostrarse', $html);
            $this->assertStringNotContainsString('COLEGIATURA-OCULTA', $html);
            $this->assertStringNotContainsString('LABORATORIO FISSAL', $html);
            $this->assertStringNotContainsString('Documento generado', $html);
            $this->assertStringNotContainsString('Orden N.°', $html);
        } finally {
            File::delete($absoluteSealPath);
        }
    }
}
