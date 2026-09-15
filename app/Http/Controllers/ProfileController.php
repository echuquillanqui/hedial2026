<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'dni' => ['nullable', 'digits:8', Rule::unique('users')->ignore($user->id)],
            'license_number' => ['nullable', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'specialty_number' => ['nullable', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'digital_seal' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_digital_seal' => ['nullable', 'boolean'],
        ]);

        unset($validated['digital_seal'], $validated['remove_digital_seal']);
        $previousSeal = $user->digital_seal_path;

        if ($request->hasFile('digital_seal')) {
            $validated['digital_seal_path'] = $request->file('digital_seal')
                ->store('users/digital-seals', 'public');
        } elseif ($request->boolean('remove_digital_seal')) {
            $validated['digital_seal_path'] = null;
        }

        $user->update($validated);

        if ($previousSeal && ($request->hasFile('digital_seal') || $request->boolean('remove_digital_seal'))) {
            Storage::disk('public')->delete($previousSeal);
        }

        return back()->with('profile_success', 'Tus datos se actualizaron correctamente.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('password_success', 'Tu contraseña se actualizó correctamente.');
    }
}
