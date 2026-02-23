<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::orderBy('name', 'asc')->get(); 
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
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
        ]);

        User::create([
            'name'             => $request->name,
            'username'         => $request->username,
            'email'            => $request->email,
            'password'         => Hash::make($request->password),
            'profession'       => $request->profession,
            'license_number'   => $request->license_number,
            'specialty_number' => $request->specialty_number,
        ]);

        return redirect()->route('users.index')->with('success', 'Personal registrado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
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
        ]);

        $data = $request->only([
            'name', 
            'username', 
            'email', 
            'profession', 
            'license_number', 
            'specialty_number'
        ]);

        // Solo actualiza la contraseña si el usuario ingresó una nueva
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Datos del personal actualizados.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Evita que el usuario actual se elimine a sí mismo
        if (auth()->id() === $user->id) {
            return redirect()->route('users.index')->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuario eliminado con éxito.');
    }
}
