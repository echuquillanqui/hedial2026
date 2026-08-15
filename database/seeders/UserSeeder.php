<?php

namespace Database\Seeders;

use App\Models\Sede;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $huancayo = Sede::query()->where('name', 'Huancayo')->first();

        if (! $huancayo) {
            throw new RuntimeException('Debe ejecutar SedeSeeder antes de UserSeeder.');
        }

        $admin = User::create([
            'name' => 'Raúl Eduardo Chuquillanqui Yupanqui',
            'username' => 'rchuquillanqui',
            'dni' => '46589634',
            'email' => 'raul@hemodial.com',
            'password' => Hash::make('12345678'),
            'profession' => 'Ingeniero de Sistemas',
        ]);
        $admin->sedes()->attach($huancayo);

        $nurses = [
            ['name' => 'Lic. Ana Martínez', 'username' => 'amartinez', 'dni' => '87654321', 'email' => 'ana@hemodial.com', 'license_number' => 'CEP 54321'],
            ['name' => 'Lic. Beatriz Salazar', 'username' => 'bsalazar', 'dni' => '87654322', 'email' => 'beatriz@hemodial.com', 'license_number' => 'CEP 54322'],
            ['name' => 'Lic. Carmen Rojas', 'username' => 'crojas', 'dni' => '87654323', 'email' => 'carmen@hemodial.com', 'license_number' => 'CEP 54323'],
            ['name' => 'Lic. Diego Quispe', 'username' => 'dquispe', 'dni' => '87654324', 'email' => 'diego@hemodial.com', 'license_number' => 'CEP 54324'],
            ['name' => 'Lic. Elena Paredes', 'username' => 'eparedes', 'dni' => '87654325', 'email' => 'elena@hemodial.com', 'license_number' => 'CEP 54325'],
        ];

        $nephrologists = [
            ['name' => 'Dr. César Valverde Cupe', 'username' => 'cvalverde', 'dni' => '76543211', 'email' => 'cesar@hemodial.com', 'license_number' => 'CMP 12345', 'specialty_number' => 'RNE 6789'],
            ['name' => 'Dra. Gabriela Mendoza', 'username' => 'gmendoza', 'dni' => '76543212', 'email' => 'gabriela@hemodial.com', 'license_number' => 'CMP 12346', 'specialty_number' => 'RNE 6790'],
            ['name' => 'Dr. Hugo Fernández', 'username' => 'hfernandez', 'dni' => '76543213', 'email' => 'hugo@hemodial.com', 'license_number' => 'CMP 12347', 'specialty_number' => 'RNE 6791'],
            ['name' => 'Dra. Isabel Torres', 'username' => 'itorres', 'dni' => '76543214', 'email' => 'isabel@hemodial.com', 'license_number' => 'CMP 12348', 'specialty_number' => 'RNE 6792'],
            ['name' => 'Dr. Jorge Huamán', 'username' => 'jhuaman', 'dni' => '76543215', 'email' => 'jorge@hemodial.com', 'license_number' => 'CMP 12349', 'specialty_number' => 'RNE 6793'],
        ];

        foreach ($nurses as $nurseData) {
            $nurse = User::create($nurseData + [
                'password' => Hash::make('password'),
                'profession' => 'ENFERMERA',
            ]);
            $nurse->sedes()->attach($huancayo);
        }

        foreach ($nephrologists as $nephrologistData) {
            $nephrologist = User::create($nephrologistData + [
                'password' => Hash::make('password'),
                'profession' => 'MEDICO',
            ]);
            $nephrologist->sedes()->attach($huancayo);
        }
    }
}
