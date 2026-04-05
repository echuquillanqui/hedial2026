<?php

namespace App\Support;

use App\Models\Sede;
use Illuminate\Support\Facades\Auth;

class CurrentSede
{
    public static function id(): ?int
    {
        return session('current_sede_id');
    }

    public static function model(): ?Sede
    {
        $id = self::id();

        if (! $id) {
            return null;
        }

        return Sede::find($id);
    }

    public static function set(Sede $sede): void
    {
        session([
            'current_sede_id' => $sede->id,
            'current_sede_name' => $sede->name,
        ]);
    }

    public static function clear(): void
    {
        session()->forget(['current_sede_id', 'current_sede_name']);
    }

    public static function resolveForAuthenticatedUser(): ?Sede
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        $selectedId = self::id();

        if ($selectedId) {
            $selected = $user->sedes()->whereKey($selectedId)->first();
            if ($selected) {
                return $selected;
            }
        }

        $sedes = $user->sedes()->where('is_active', true)->orderBy('name')->get();

        if ($sedes->count() === 1) {
            self::set($sedes->first());
            return $sedes->first();
        }

        return null;
    }
}
