<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dni' => $this->faker->unique()->numerify('########'),
            'first_name' => $this->faker->firstName(),
            'other_names' => $this->faker->firstName(),
            'surname' => $this->faker->lastName(),
            'last_name' => $this->faker->lastName(),
            'affiliation_code' => $this->faker->numerify('##########'),
            'medical_history_number' => $this->faker->numerify('HC-####'),
            'insurance_type' => $this->faker->randomElement(['SIS', 'EsSalud', 'SaludPol']),
            'gender' => $this->faker->randomElement(['M', 'F']),
            'birth_date' => $this->faker->date('Y-m-d', '2000-01-01'),
            'address' => $this->faker->address(),
            'district' => 'Huancayo',
            'department' => 'Junín',
            'secuencia' => $this->faker->randomElement(['L-M-V', 'M-J-S']),
            'turno' => $this->faker->randomElement(['1', '2', '3', '4']),
            'modulo' => $this->faker->randomElement(['1', '2', '3', '4']),
        ];
    }
}
