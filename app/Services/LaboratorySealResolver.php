<?php

namespace App\Services;

use App\Models\User;

class LaboratorySealResolver
{
    public function resolve(): ?User
    {
        return User::query()
            ->whereNotNull('digital_seal_path')
            ->where(function ($query) {
                $query->whereHas('roles', fn ($roles) => $roles->where('name', 'laboratorio'))
                    ->orWhere('profession', 'like', '%LABORATOR%')
                    ->orWhere('profession', 'like', '%TECN%LOG%');
            })
            ->orderBy('id')
            ->get()
            ->first(fn (User $user) => file_exists(
                storage_path('app/public/'.$user->digital_seal_path)
            ));
    }
}
