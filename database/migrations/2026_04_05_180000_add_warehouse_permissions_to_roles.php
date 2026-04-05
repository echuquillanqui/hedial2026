<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $permissionNames = [
            'warehouse.requests.view',
            'warehouse.requests.create',
            'warehouse.requests.update.status',
            'warehouse.requests.dispatch',
            'warehouse.requests.receive',
            'warehouse.requests.print',
        ];

        $permissionIds = collect($permissionNames)
            ->mapWithKeys(function (string $name) use ($now) {
                $permissionId = DB::table('permissions')
                    ->where('name', $name)
                    ->where('guard_name', 'web')
                    ->value('id');

                if (!$permissionId) {
                    $permissionId = DB::table('permissions')->insertGetId([
                        'name' => $name,
                        'guard_name' => 'web',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                return [$name => $permissionId];
            });

        $roleNames = ['admin', 'almacen', 'superadmin'];

        $roles = DB::table('roles')
            ->whereIn('name', $roleNames)
            ->where('guard_name', 'web')
            ->pluck('id', 'name');

        foreach ($roles as $roleId) {
            foreach ($permissionIds as $permissionId) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (!$exists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permissionId,
                        'role_id' => $roleId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $permissionNames = [
            'warehouse.requests.view',
            'warehouse.requests.create',
            'warehouse.requests.update.status',
            'warehouse.requests.dispatch',
            'warehouse.requests.receive',
            'warehouse.requests.print',
        ];

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $permissionNames)
            ->where('guard_name', 'web')
            ->pluck('id');

        if ($permissionIds->isEmpty()) {
            return;
        }

        DB::table('role_has_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        DB::table('model_has_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        DB::table('permissions')
            ->whereIn('id', $permissionIds)
            ->delete();
    }
};
