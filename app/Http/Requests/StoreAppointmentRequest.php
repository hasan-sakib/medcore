<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use App\Support\TenantRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Appointment::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', TenantRule::exists('patients', 'id')],
            'doctor_id' => ['required', 'integer', TenantRule::exists('users', 'id')],
            'department_id' => ['nullable', 'integer', TenantRule::exists('departments', 'id')],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
