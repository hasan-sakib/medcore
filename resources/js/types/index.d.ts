export interface Tenant {
    id: number;
    name: string;
    slug: string;
    domain: string | null;
    status: 'active' | 'suspended' | 'trial';
    subscription_plan: string;
    trial_ends_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface User {
    id: number;
    name: string;
    email: string;
    tenant_id: number | null;
    email_verified_at: string | null;
    two_factor_confirmed_at: string | null;
}

export interface PageProps {
    auth: {
        user: User | null;
    };
    tenant: Pick<Tenant, 'id' | 'name' | 'slug'> | null;
    permissions: string[];
    roles: string[];
    flash: {
        success: string | null;
        error: string | null;
    };
}

export type PaginatedResource<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

// ── Phase 2: EMR Types ──────────────────────────────────────────────────────

export interface Department {
    id: number;
    tenant_id: number;
    name: string;
    code: string;
    description: string | null;
    is_active: boolean;
    patients_count?: number;
    encounters_count?: number;
    created_at: string;
    updated_at: string;
}

export interface DoctorSchedule {
    id: number;
    tenant_id: number;
    user_id: number;
    department_id: number;
    day_of_week: number; // 0=Sun … 6=Sat
    start_time: string;  // "08:00:00"
    end_time: string;
    slot_duration: number;
    max_patients: number;
    is_active: boolean;
    effective_from: string | null;
    effective_until: string | null;
    doctor?: Pick<User, 'id' | 'name'>;
    department?: Pick<Department, 'id' | 'name'>;
    created_at: string;
    updated_at: string;
}

export interface Patient {
    id: number;
    tenant_id: number;
    mrn: string;
    // PHI fields — decrypted server-side before Inertia serialization
    first_name: string;
    last_name: string;
    date_of_birth: string;       // "YYYY-MM-DD"
    gender: 'male' | 'female' | 'other' | null;
    national_id: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    blood_group: string | null;
    emergency_contact: string | null;
    status: 'active' | 'inactive' | 'deceased';
    registered_at: string;
    registered_by: number | null;
    department_id: number | null;
    department?: Pick<Department, 'id' | 'name'> | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

export interface Appointment {
    id: number;
    tenant_id: number;
    patient_id: number;
    doctor_id: number;
    department_id: number | null;
    scheduled_at: string;        // ISO datetime
    ends_at: string;
    status: 'pending' | 'confirmed' | 'checked_in' | 'completed' | 'cancelled' | 'no_show';
    reason: string | null;
    notes: string | null;
    cancelled_by: number | null;
    cancelled_at: string | null;
    cancellation_reason: string | null;
    patient?: Patient;
    doctor?: Pick<User, 'id' | 'name'>;
    department?: Pick<Department, 'id' | 'name'> | null;
    encounter?: Encounter | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

export interface Vital {
    id: number;
    tenant_id: number;
    encounter_id: number;
    patient_id: number;
    recorded_by: number;
    recorded_at: string;
    temperature_c: number | null;
    pulse_bpm: number | null;
    bp_systolic: number | null;
    bp_diastolic: number | null;
    spo2_pct: number | null;
    respiratory_rate: number | null;
    weight_kg: number | null;
    height_cm: number | null;
    bmi: number | null;
    glucose_mmol: number | null;
    pain_scale: number | null;
    notes: string | null;
    recordedBy?: Pick<User, 'id' | 'name'>;
    created_at: string;
    updated_at: string;
}

export interface ClinicalNote {
    id: number;
    tenant_id: number;
    encounter_id: number;
    author_id: number;
    note_type: 'soap' | 'progress' | 'procedure' | 'discharge_summary' | 'referral';
    subjective: string | null;
    objective: string | null;
    assessment: string | null;
    plan: string | null;
    body: string | null;
    is_signed: boolean;
    signed_at: string | null;
    author?: Pick<User, 'id' | 'name'>;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

export interface Diagnosis {
    id: number;
    icd10_code: string;
    description: string;
    category: string | null;
    is_active: boolean;
}

export interface EncounterDiagnosis {
    id: number;
    tenant_id: number;
    encounter_id: number;
    diagnosis_id: number;
    type: 'primary' | 'secondary' | 'complication' | 'admitting';
    onset_date: string | null;
    resolved_at: string | null;
    notes: string | null;
    created_by: number;
    diagnosis?: Diagnosis;
    createdBy?: Pick<User, 'id' | 'name'>;
    created_at: string;
    updated_at: string;
}

export interface Encounter {
    id: number;
    tenant_id: number;
    patient_id: number;
    appointment_id: number | null;
    attending_doctor_id: number;
    department_id: number | null;
    encounter_type: 'outpatient' | 'inpatient' | 'emergency' | 'teleconsult';
    status: 'open' | 'in_progress' | 'completed' | 'cancelled';
    chief_complaint: string | null;
    encounter_date: string;
    admitted_at: string | null;
    discharged_at: string | null;
    patient?: Patient;
    attendingDoctor?: Pick<User, 'id' | 'name'>;
    department?: Pick<Department, 'id' | 'name'> | null;
    appointment?: Appointment | null;
    clinicalNotes?: ClinicalNote[];
    vitals?: Vital[];
    encounterDiagnoses?: EncounterDiagnosis[];
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

export type AppointmentSlot = {
    start: string; // ISO datetime
    end: string;
};
