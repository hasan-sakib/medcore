<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    public function definition(): array
    {
        // PHI fields are plain text here; the model's EncryptedPhi cast and
        // HasBlindIndexes::setPhiField will handle encryption + blind indexes
        // when the factory calls Patient::create().
        //
        // NOTE: factories set attributes via fill/create which goes through
        // model casts, so EncryptedPhi encrypts on set. Blind indexes must be
        // set via setPhiField. We override configure() below to do this.
        return [
            'tenant_id' => Tenant::factory(),
            'mrn' => 'MRN-0000-'.strtoupper($this->faker->unique()->lexify('????????')),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'date_of_birth' => $this->faker->dateTimeBetween('-80 years', '-1 year')->format('Y-m-d'),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'national_id' => $this->faker->numerify('##########'),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->safeEmail(),
            'address' => $this->faker->address(),
            'blood_group' => $this->faker->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'emergency_contact' => $this->faker->name().' ('.$this->faker->phoneNumber().')',
            'status' => 'active',
        ];
    }

    /**
     * After creating, compute blind indexes for PHI fields via setPhiField.
     * This is necessary because the factory sets attributes directly through
     * fill(), which triggers EncryptedPhi cast but not blind index computation.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Patient $patient) {
            $blindIndexedFields = ['first_name', 'last_name', 'national_id', 'phone'];
            $needsUpdate = false;

            foreach ($blindIndexedFields as $field) {
                // Re-read the decrypted value and re-set via setPhiField to
                // ensure blind index columns are populated
                $plaintext = $patient->$field;
                if ($plaintext !== null) {
                    $patient->setPhiField($field, $plaintext);
                    $needsUpdate = true;
                }
            }

            if ($needsUpdate) {
                $patient->saveQuietly();
            }
        });
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(['tenant_id' => $tenant->id]);
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }

    public function deceased(): static
    {
        return $this->state(['status' => 'deceased']);
    }
}
