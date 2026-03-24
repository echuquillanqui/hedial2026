<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
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
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
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
