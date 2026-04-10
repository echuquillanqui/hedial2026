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

            'referrals.view', 'referrals.create', 'referrals.edit', 'referrals.delete', 'referrals.print',

            'orders.view', 'orders.create', 'orders.edit', 'orders.delete',
            'medicals.view', 'medicals.create', 'medicals.edit', 'medicals.delete',
            'nurses.view', 'nurses.create', 'nurses.edit', 'nurses.delete',

            'reports.export.pdf', 'reports.export.excel',

            'warehouse.requests.view',
            'warehouse.requests.create',
            'warehouse.requests.update.status',
            'warehouse.requests.dispatch',
            'warehouse.requests.receive',
            'warehouse.requests.print',
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
                'referrals.view', 'referrals.create', 'referrals.edit', 'referrals.print',
                'orders.view', 'orders.create', 'orders.edit',
                'medicals.view', 'medicals.create', 'medicals.edit',
                'nurses.view', 'nurses.create', 'nurses.edit',
                'reports.export.pdf', 'reports.export.excel',

            'warehouse.requests.view',
            'warehouse.requests.create',
            'warehouse.requests.update.status',
            'warehouse.requests.dispatch',
            'warehouse.requests.receive',
            'warehouse.requests.print',
            ],
            'medico' => [
                'dashboard.view',
                'patients.view',
                'referrals.view', 'referrals.create', 'referrals.edit', 'referrals.print',
                'orders.view', 'orders.create', 'orders.edit',
                'medicals.view', 'medicals.create', 'medicals.edit',
            ],
            'enfermeria' => [
                'dashboard.view',
                'patients.view',
                'nurses.view', 'nurses.create', 'nurses.edit',
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
