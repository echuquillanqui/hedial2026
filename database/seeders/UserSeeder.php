<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Raúl Eduardo Chuquillanqui Yupanqui',
            'username' => 'rchuquillanqui',
            'dni' => '46589634',
            'email' => 'raul@hemodial.com',
            'password' => Hash::make('12345678'),
            'profession' => 'Ingeniero de Sistemas',
        ]);
        // Usuario Médico de Prueba
        User::create([
            'name' => 'Dr. César Valverde Cupe',
            'username' => 'cvalverde',
            'dni' => '12345678',
            'email' => 'cesar@hemodial.com',
            'password' => Hash::make('password'),
            'license_number' => 'CMP 12345',
            'specialty_number' => 'RNE 6789',
            'profession' => 'MEDICO',
        ]);

        // Usuario Enfermería
        User::create([
            'name' => 'Lic. Ana Martínez',
            'username' => 'amartinez',
            'dni' => '87654321',
            'email' => 'ana@hemodial.com',
            'password' => Hash::make('password'),
            'license_number' => 'CEP 54321',
            'profession' => 'ENFERMERA',
        ]);
    }
}
