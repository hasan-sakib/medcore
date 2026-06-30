<?php

namespace App\Services;

use App\Models\Patient;
use App\Support\TenantManager;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class PatientService
{
    /**
     * Search patients by blind-indexed PHI fields or MRN (plaintext, exact match).
     * Logs a PHI read on every returned record.
     */
    public function search(string $term, int $perPage = 20): LengthAwarePaginator
    {
        // MRN is plaintext — try exact match first
        if (preg_match('/^MRN-/i', $term)) {
            $result = Patient::where('mrn', strtoupper($term))->paginate($perPage);
            $result->each(fn ($p) => $p->logPhiRead('patient_search_mrn'));

            return $result;
        }

        // Blind-index search across name, national ID, and phone
        $key = base64_decode(config('phi.blind_index_key'));
        $hash = hash_hmac('sha256', $term, $key);

        $patients = Patient::where(function ($q) use ($hash) {
            $q->where('first_name_index', $hash)
                ->orWhere('last_name_index', $hash)
                ->orWhere('national_id_index', $hash)
                ->orWhere('phone_index', $hash);
        })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $patients->each(fn ($p) => $p->logPhiRead('patient_search'));

        return $patients;
    }

    /**
     * Create a new patient, computing blind indexes for searchable PHI fields.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Patient
    {
        $data['mrn'] ??= $this->generateMrn();
        $data['registered_by'] = Auth::id();
        $data['registered_at'] = now();

        $patient = new Patient;

        // Non-PHI fields
        $patient->mrn = $data['mrn'];
        $patient->status = $data['status'] ?? 'active';
        $patient->registered_at = $data['registered_at'];
        $patient->registered_by = $data['registered_by'];
        $patient->department_id = $data['department_id'] ?? null;

        // PHI fields — use setPhiField to encrypt + compute blind index simultaneously
        foreach ($this->phiFields() as $field) {
            if (isset($data[$field])) {
                $patient->setPhiField($field, $data[$field]);
            }
        }

        $patient->save();

        return $patient;
    }

    /**
     * Update patient demographics, re-computing blind indexes for dirty PHI fields.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Patient $patient, array $data): Patient
    {
        // Non-PHI fields
        foreach (['status', 'department_id'] as $field) {
            if (array_key_exists($field, $data)) {
                $patient->$field = $data[$field];
            }
        }

        // PHI fields
        foreach ($this->phiFields() as $field) {
            if (array_key_exists($field, $data)) {
                $patient->setPhiField($field, $data[$field]);
            }
        }

        $patient->save();
        $patient->logPhiRead('patient_update');

        return $patient->fresh();
    }

    /** @return string[] */
    private function phiFields(): array
    {
        return [
            'first_name', 'last_name', 'date_of_birth', 'gender',
            'national_id', 'phone', 'email', 'address',
            'blood_group', 'emergency_contact',
        ];
    }

    private function generateMrn(): string
    {
        $tenantId = app(TenantManager::class)->current()->id;

        return 'MRN-'.str_pad((string) $tenantId, 4, '0', STR_PAD_LEFT)
            .'-'.strtoupper(bin2hex(random_bytes(4)));
    }
}
