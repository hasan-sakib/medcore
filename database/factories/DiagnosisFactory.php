<?php

namespace Database\Factories;

use App\Models\Diagnosis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Diagnosis>
 */
class DiagnosisFactory extends Factory
{
    private static array $chapters = [
        'Certain infectious and parasitic diseases',
        'Neoplasms',
        'Diseases of the blood',
        'Endocrine, nutritional and metabolic diseases',
        'Mental and behavioural disorders',
        'Diseases of the nervous system',
        'Diseases of the circulatory system',
        'Diseases of the respiratory system',
        'Diseases of the digestive system',
        'Diseases of the musculoskeletal system',
    ];

    public function definition(): array
    {
        $letter = $this->faker->randomElement(['A', 'B', 'C', 'E', 'I', 'J', 'K', 'M', 'N', 'Z']);

        return [
            'icd10_code' => $letter.$this->faker->unique()->numerify('##.#'),
            'description' => $this->faker->sentence(4),
            'category' => $this->faker->randomElement(self::$chapters),
            'is_active' => true,
        ];
    }
}
