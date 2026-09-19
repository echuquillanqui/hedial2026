<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_their_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Mi perfil');
    }

    public function test_laboratory_report_is_available_from_the_reports_navigation_menu(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::create(['name' => 'laboratory.results.view']));

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSeeInOrder(['Reportes', 'Reporte Lab'])
            ->assertSee(route('laboratory.results.index'), false);
    }

    public function test_user_can_update_their_personal_data(): void
    {
        $user = User::factory()->create(['username' => 'anterior']);

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Carolina Molina',
            'username' => 'carolina.molina',
            'email' => 'carolina@example.com',
            'dni' => '12345678',
            'license_number' => 'CMP-123',
            'specialty_number' => 'RNE-456',
        ])->assertSessionHasNoErrors()->assertSessionHas('profile_success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Carolina Molina',
            'username' => 'carolina.molina',
            'email' => 'carolina@example.com',
        ]);
    }

    public function test_user_must_confirm_current_password_before_changing_it(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'incorrecta',
            'password' => 'nueva-clave-segura',
            'password_confirmation' => 'nueva-clave-segura',
        ])->assertSessionHasErrorsIn('updatePassword', 'current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_user_can_change_their_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'password',
            'password' => 'nueva-clave-segura',
            'password_confirmation' => 'nueva-clave-segura',
        ])->assertSessionHasNoErrors()->assertSessionHas('password_success');

        $this->assertTrue(Hash::check('nueva-clave-segura', $user->fresh()->password));
    }
}
