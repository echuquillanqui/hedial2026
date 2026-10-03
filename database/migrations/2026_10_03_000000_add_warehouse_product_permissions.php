<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSIONS = [
        'warehouse.products.create',
        'warehouse.products.edit',
        'warehouse.products.delete',
    ];

    public function up(): void
    {
        $now = now();

        $permissionIds = collect(self::PERMISSIONS)->map(function (string $name) use ($now) {
            return DB::table('permissions')->where(['name' => $name, 'guard_name' => 'web'])->value('id')
                ?: DB::table('permissions')->insertGetId([
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
        });

        $roleIds = DB::table('roles')
            ->whereIn('name', ['logistica', 'superadmin'])
            ->where('guard_name', 'web')
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', self::PERMISSIONS)
            ->where('guard_name', 'web')
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
