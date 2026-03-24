<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:users.view')->only(['index']);
        $this->middleware('permission:users.create')->only(['store']);
        $this->middleware('permission:users.edit')->only(['update']);
        $this->middleware('permission:users.delete')->only(['destroy']);
        $this->middleware('permission:roles.create')->only(['storeRole']);
        $this->middleware('permission:permissions.create')->only(['storePermission']);
        $this->middleware('permission:users.assign.massive')->only(['bulkAssignPermissions', 'bulkUpdatePermissions']);
        $this->middleware('permission:users.assign.individual')->only(['store', 'update', 'permissionsManager', 'updateUserPermissions']);
    }

    public function index()
    {
        $users = User::with(['roles', 'permissions'])->orderBy('name', 'asc')->get();
        $roles = Role::with('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        return view('users.index', compact('users', 'roles', 'permissions'));
    }

    public function permissionsManager()
    {
        $users = User::with('permissions')->orderBy('name', 'asc')->get();
        $roles = Role::with('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        $permissionManagerUsers = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'permissions' => $user->permissions->pluck('name')->values(),
            ];
        })->values();

        $permissionCatalog = $permissions->pluck('name')->values();
        $rolesCatalog = $roles->pluck('name')->values();

        return view('users.permissions-manager', compact(
            'users',
            'roles',
            'permissions',
            'permissionManagerUsers',
            'permissionCatalog',
            'rolesCatalog'
        ));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'username'         => 'required|string|max:255|unique:users,username',
            'email'            => 'required|string|email|max:255|unique:users,email',
            'password'         => 'required|string|min:8',
            'profession'       => ['nullable', Rule::in(['MEDICO', 'ENFERMERA', 'ADMINISTRATIVO', 'SUPERADMIN'])],
            'license_number'   => 'nullable|string|unique:users,license_number',
            'specialty_number' => 'nullable|string|unique:users,specialty_number',
            'roles'            => 'nullable|array',
            'roles.*'          => 'exists:roles,name',
            'permissions'      => 'nullable|array',
            'permissions.*'    => 'exists:permissions,name',
        ]);

        $user = User::create([
            'name'             => $request->name,
            'username'         => $request->username,
            'email'            => $request->email,
            'password'         => Hash::make($request->password),
            'profession'       => $request->profession,
            'license_number'   => $request->license_number,
            'specialty_number' => $request->specialty_number,
        ]);

        $user->syncRoles($request->input('roles', []));
        $user->syncPermissions($request->input('permissions', []));

        return redirect()->route('users.index')->with('success', 'Personal registrado correctamente.');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'username'         => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email'            => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password'         => 'nullable|string|min:8',
            'profession'       => ['nullable', Rule::in(['MEDICO', 'ENFERMERA', 'ADMINISTRATIVO', 'SUPERADMIN'])],
            'license_number'   => ['nullable', 'string', Rule::unique('users')->ignore($user->id)],
            'specialty_number' => ['nullable', 'string', Rule::unique('users')->ignore($user->id)],
            'roles'            => 'nullable|array',
            'roles.*'          => 'exists:roles,name',
            'permissions'      => 'nullable|array',
            'permissions.*'    => 'exists:permissions,name',
        ]);

        $data = $request->only([
            'name',
            'username',
            'email',
            'profession',
            'license_number',
            'specialty_number'
        ]);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->syncRoles($request->input('roles', []));
        $user->syncPermissions($request->input('permissions', []));

        return redirect()->route('users.index')->with('success', 'Datos del personal actualizados.');
    }

    public function updateUserPermissions(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $user = User::findOrFail($request->user_id);
        $user->syncPermissions($request->input('permissions', []));

        return redirect()->route('users.permissions-manager')->with('success', 'Permisos actualizados correctamente para el usuario seleccionado.');
    }

    public function bulkUpdatePermissions(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'mode' => 'required|in:add,replace,remove',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $users = User::whereIn('id', $request->user_ids)->get();
        $permissions = $request->input('permissions', []);

        foreach ($users as $user) {
            if ($request->mode === 'replace') {
                $user->syncPermissions($permissions);
                continue;
            }

            if ($request->mode === 'add') {
                $user->givePermissionTo($permissions);
                continue;
            }

            $user->revokePermissionTo($permissions);
        }

        return redirect()->route('users.permissions-manager')->with('success', 'Asignación masiva de permisos completada.');
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return redirect()->route('users.index')->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuario eliminado con éxito.');
    }

    public function storeRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);
        $role->syncPermissions($request->input('permissions', []));

        return redirect()->route('users.index')->with('success', 'Rol creado correctamente.');
    }

    public function storePermission(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150|unique:permissions,name',
        ]);

        Permission::create(['name' => $request->name, 'guard_name' => 'web']);

        return redirect()->route('users.index')->with('success', 'Permiso creado correctamente.');
    }

    public function bulkAssignPermissions(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'mode' => 'required|in:replace,add',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $users = User::whereIn('id', $request->user_ids)->get();

        foreach ($users as $user) {
            if ($request->mode === 'replace') {
                $user->syncRoles($request->input('roles', []));
                $user->syncPermissions($request->input('permissions', []));
            } else {
                if ($request->filled('roles')) {
                    $user->assignRole($request->input('roles', []));
                }
                if ($request->filled('permissions')) {
                    $user->givePermissionTo($request->input('permissions', []));
                }
            }
        }

        return redirect()->route('users.index')->with('success', 'Asignación masiva completada.');
    }
}
