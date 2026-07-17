<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
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
            'mrn' => 'RM-' . $this->faker->unique()->numerify('######'),
            'nik' => $this->faker->numerify('################'),
            'name' => $this->faker->name(),
            'gender' => $this->faker->randomElement(['L', 'P']),
            'date_of_birth' => $this->faker->dateTimeBetween('-80 years', '-5 years'),
            'address' => $this->faker->address(),
            'phone' => $this->faker->numerify('08##########'),
        ];
    }
}
