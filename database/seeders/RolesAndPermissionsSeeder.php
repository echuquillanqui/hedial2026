<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Sede;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',

            'users.view', 'users.create', 'users.edit', 'users.delete', 'users.assign.individual', 'users.assign.massive',
            'roles.create', 'roles.edit', 'roles.assign.permissions',
            'permissions.create', 'permissions.edit',

            'patients.view', 'patients.create', 'patients.edit', 'patients.delete',
            'initial_history.view', 'initial_history.create', 'initial_history.update', 'initial_history.print',
            'consents.view', 'consents.create', 'consents.print',

            'referrals.view', 'referrals.create', 'referrals.edit', 'referrals.delete', 'referrals.print',

            'orders.view', 'orders.create', 'orders.edit', 'orders.delete',
            'medicals.view', 'medicals.create', 'medicals.edit', 'medicals.delete',
            'nurses.view', 'nurses.create', 'nurses.edit', 'nurses.delete',
            'nephrology.view', 'nephrology.create', 'nephrology.update', 'nephrology.print',

            'fua.view', 'fua.generate', 'fua.responsible.update', 'fua.configuration.manage', 'fua.correction.create',
            'laboratory.results.view', 'laboratory.orders.create', 'laboratory.results.update', 'laboratory.catalog.manage',
            'materials.view', 'materials.manage', 'audit.view',

            'nutrition.view', 'nutrition.create', 'nutrition.update', 'nutrition.print',
            'nutrition.mis.view', 'nutrition.mis.create', 'nutrition.fua.view', 'nutrition.fua.generate',
            'psychology.view', 'psychology.create', 'psychology.update', 'psychology.print',
            'psychology.eq5d.view', 'psychology.eq5d.create', 'psychology.fua.view', 'psychology.fua.generate',
            'social_work.view', 'social_work.create', 'social_work.update', 'social_work.print',
            'social_work.fua.view', 'social_work.fua.generate',

            'reports.export.pdf', 'reports.export.excel',

            'warehouse.requests.view',
            'warehouse.requests.create',
            'warehouse.requests.update.status',
            'warehouse.requests.dispatch',
            'warehouse.requests.receive',
            'warehouse.requests.print',
            'warehouse.configuration.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdminRole = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $superAdminRole->syncPermissions(Permission::all());

        $roles = [
            'admin' => [
                'dashboard.view',
                'users.view', 'users.create', 'users.edit', 'users.assign.individual', 'users.assign.massive',
                'roles.create', 'roles.assign.permissions', 'permissions.create',
                'patients.view', 'patients.create', 'patients.edit',
                'initial_history.view', 'initial_history.create', 'initial_history.update', 'initial_history.print',
                'consents.view', 'consents.create', 'consents.print',
                'referrals.view', 'referrals.create', 'referrals.edit', 'referrals.print',
                'orders.view', 'orders.create', 'orders.edit',
                'medicals.view', 'medicals.create', 'medicals.edit',
                'nurses.view', 'nurses.create', 'nurses.edit',
                'nephrology.view', 'nephrology.create', 'nephrology.update', 'nephrology.print',
                'fua.view', 'fua.generate', 'fua.responsible.update', 'fua.configuration.manage', 'fua.correction.create',
                'laboratory.results.view', 'laboratory.orders.create', 'laboratory.results.update', 'laboratory.catalog.manage',
                'materials.view', 'materials.manage', 'audit.view',
                'nutrition.view', 'nutrition.create', 'nutrition.update', 'nutrition.print',
                'nutrition.mis.view', 'nutrition.mis.create', 'nutrition.fua.view', 'nutrition.fua.generate',
                'psychology.view', 'psychology.create', 'psychology.update', 'psychology.print',
                'psychology.eq5d.view', 'psychology.eq5d.create', 'psychology.fua.view', 'psychology.fua.generate',
                'social_work.view', 'social_work.create', 'social_work.update', 'social_work.print',
                'social_work.fua.view', 'social_work.fua.generate',
                'reports.export.pdf', 'reports.export.excel',

            'warehouse.requests.view',
            'warehouse.requests.create',
            'warehouse.requests.receive',
            'warehouse.requests.print',
            ],
            'medico' => [
                'dashboard.view',
                'patients.view',
                'initial_history.view', 'initial_history.create', 'initial_history.update', 'initial_history.print',
                'consents.view', 'consents.create', 'consents.print',
                'referrals.view', 'referrals.create', 'referrals.edit', 'referrals.print',
                'orders.view', 'orders.create', 'orders.edit',
                'medicals.view', 'medicals.create', 'medicals.edit',
                'nephrology.view', 'nephrology.create', 'nephrology.update', 'nephrology.print',
                'fua.view', 'fua.generate', 'fua.responsible.update',
                'laboratory.results.view', 'laboratory.orders.create',
                'materials.view', 'audit.view',
            ],
            'enfermeria' => [
                'dashboard.view',
                'patients.view',
                'nurses.view', 'nurses.create', 'nurses.edit',
                'fua.view', 'laboratory.results.view', 'materials.view',
            ],
            'recepcion' => [
                'dashboard.view',
                'patients.view', 'patients.create', 'patients.edit',
                'referrals.view', 'referrals.create',
            ],

            'almacen' => [
                'dashboard.view',
                'warehouse.requests.view', 'warehouse.requests.create', 'warehouse.requests.update.status',
                'warehouse.requests.dispatch', 'warehouse.requests.receive', 'warehouse.requests.print',
            ],
            'logistica' => [
                'dashboard.view',
                'warehouse.requests.view', 'warehouse.requests.create', 'warehouse.requests.update.status',
                'warehouse.requests.dispatch', 'warehouse.requests.print',
                'warehouse.configuration.manage',
            ],
            'nutricionista' => [
                'dashboard.view', 'patients.view', 'laboratory.results.view',
                'nutrition.view', 'nutrition.create', 'nutrition.update', 'nutrition.print',
                'nutrition.mis.view', 'nutrition.mis.create', 'nutrition.fua.view', 'nutrition.fua.generate',
            ],
            'psicologo' => [
                'dashboard.view', 'patients.view',
                'psychology.view', 'psychology.create', 'psychology.update', 'psychology.print',
                'psychology.eq5d.view', 'psychology.eq5d.create', 'psychology.fua.view', 'psychology.fua.generate',
            ],
            'trabajo_social' => [
                'dashboard.view', 'patients.view',
                'social_work.view', 'social_work.create', 'social_work.update', 'social_work.print',
                'social_work.fua.view', 'social_work.fua.generate',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }

        $adminRole = Role::firstWhere(['name' => 'admin', 'guard_name' => 'web']);
        Sede::query()->where('is_active', true)->get()->each(function (Sede $sede) use ($adminRole) {
            $base = Str::of($sede->name)->lower()->ascii()->replace(' ', '.');
            $username = "admin.{$base}";
            $email = "{$username}@hemodial.local";

            $user = User::query()->firstOrCreate(
                ['username' => $username],
                [
                    'name' => 'Administrador ' . $sede->name,
                    'email' => $email,
                    'password' => Hash::make('Admin@123456'),
                    'profession' => 'ADMINISTRATIVO',
                ]
            );

            if (! $user->wasRecentlyCreated && $user->email !== $email) {
                $user->update(['email' => $email]);
            }

            if ($adminRole) {
                $user->syncRoles([$adminRole->name]);
            }

            $user->sedes()->sync([$sede->id]);
        });

        $logisticsUser = User::query()->firstOrCreate(
            ['username' => 'logistica'],
            [
                'name' => 'Usuario General de Logística',
                'email' => 'logistica@hemodial.local',
                'password' => Hash::make('Logistica@123456'),
                'profession' => 'LOGÍSTICA',
            ]
        );
        $logisticsUser->syncRoles(['logistica']);
        $logisticsUser->sedes()->sync(Sede::query()->where('is_active', true)->pluck('id'));

        $developmentUsers = [
            ['username' => 'nutricionista', 'name' => 'Nutricionista de prueba', 'email' => 'nutricionista@hemodial.local', 'profession' => 'NUTRICIONISTA', 'role' => 'nutricionista'],
            ['username' => 'psicologo', 'name' => 'Psicólogo de prueba', 'email' => 'psicologo@hemodial.local', 'profession' => 'PSICÓLOGO', 'role' => 'psicologo'],
            ['username' => 'trabajo.social', 'name' => 'Trabajador social de prueba', 'email' => 'trabajo.social@hemodial.local', 'profession' => 'TRABAJADOR SOCIAL', 'role' => 'trabajo_social'],
        ];

        foreach ($developmentUsers as $data) {
            $role = $data['role'];
            unset($data['role']);

            $user = User::query()->firstOrCreate(
                ['username' => $data['username']],
                $data + ['password' => Hash::make('Profesional@123456')]
            );
            $user->syncRoles([$role]);
            $user->syncPermissions([]);
            $user->sedes()->syncWithoutDetaching(Sede::query()->where('is_active', true)->pluck('id'));
        }

        $ownerUser = User::where('username', 'rchuquillanqui')->first();

        if ($ownerUser) {
            $ownerUser->syncRoles(['superadmin']);
            $ownerUser->syncPermissions(Permission::all());
            $ownerUser->profession = 'SUPERADMIN';
            $ownerUser->save();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
