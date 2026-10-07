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
    portal_user?: { id: number; email: string } | null;
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

// ── Phase 3: Pharmacy, Inventory & Batch Tracking ──────────────────────────

export interface Supplier {
    id: number;
    tenant_id: number;
    name: string;
    contact_name: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    is_active: boolean;
    medicine_batches_count?: number;
    created_at: string;
    updated_at: string;
}

export interface Medicine {
    id: number;
    tenant_id: number;
    name: string;
    generic_name: string | null;
    sku: string;
    category: string | null;
    unit_type: 'tablet' | 'capsule' | 'ml' | 'unit' | 'vial';
    strength: string | null;
    min_stock_level: number;
    reorder_level: number;
    is_active: boolean;
    stock_on_hand?: number;
    created_at: string;
    updated_at: string;
}

export interface MedicineBatch {
    id: number;
    tenant_id: number;
    medicine_id: number;
    supplier_id: number | null;
    purchase_order_id: number | null;
    batch_number: string;
    lot_number: string | null;
    quantity_received: number;
    quantity_on_hand: number;
    unit_cost: number | null;
    expiry_date: string;           // "YYYY-MM-DD"
    manufactured_date: string | null;
    received_at: string;
    received_by: number | null;
    status: 'active' | 'expired' | 'quarantined' | 'depleted';
    medicine?: Medicine;
    supplier?: Pick<Supplier, 'id' | 'name'> | null;
    created_at: string;
    updated_at: string;
}

export interface StockMovement {
    id: number;
    tenant_id: number;
    medicine_id: number;
    batch_id: number;
    movement_type: 'in' | 'out' | 'adjustment' | 'return' | 'waste';
    quantity: number;              // signed: positive=in, negative=out
    reference_type: string | null;
    reference_id: number | null;
    notes: string | null;
    created_by: number;
    medicine?: Pick<Medicine, 'id' | 'name' | 'sku'>;
    batch?: Pick<MedicineBatch, 'id' | 'batch_number'>;
    createdBy?: Pick<User, 'id' | 'name'>;
    created_at: string;
    updated_at: string;
}

export interface PrescriptionItem {
    id: number;
    tenant_id: number;
    prescription_id: number;
    medicine_id: number;
    dosage_instruction: string;
    frequency: string;
    duration_days: number | null;
    quantity_prescribed: number;
    quantity_dispensed: number;
    notes: string | null;
    medicine?: Pick<Medicine, 'id' | 'name' | 'sku' | 'unit_type' | 'strength'>;
    created_at: string;
    updated_at: string;
}

export interface Prescription {
    id: number;
    tenant_id: number;
    patient_id: number;
    encounter_id: number | null;
    prescribed_by: number;
    status: 'pending' | 'partially_filled' | 'filled' | 'cancelled';
    notes: string | null;
    prescribed_at: string;
    expires_at: string | null;
    patient?: Patient;
    encounter?: Pick<Encounter, 'id' | 'encounter_date'> | null;
    prescribedBy?: Pick<User, 'id' | 'name'>;
    items?: PrescriptionItem[];
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

export interface DispenseRecord {
    id: number;
    tenant_id: number;
    prescription_id: number | null;
    prescription_item_id: number | null;
    patient_id: number;
    medicine_id: number;
    batch_id: number;
    quantity_dispensed: number;
    dispensed_by: number;
    dispensed_at: string;
    notes: string | null;
    medicine?: Pick<Medicine, 'id' | 'name' | 'sku'>;
    batch?: Pick<MedicineBatch, 'id' | 'batch_number' | 'expiry_date'>;
    dispensedBy?: Pick<User, 'id' | 'name'>;
    created_at: string;
    updated_at: string;
}

export interface PurchaseOrderItem {
    id: number;
    tenant_id: number;
    purchase_order_id: number;
    medicine_id: number;
    quantity_ordered: number;
    quantity_received: number;
    unit_price: number;
    medicine?: Pick<Medicine, 'id' | 'name' | 'sku' | 'unit_type'>;
    created_at: string;
    updated_at: string;
}

export interface PurchaseOrder {
    id: number;
    tenant_id: number;
    supplier_id: number | null;
    po_number: string;
    status: 'draft' | 'sent' | 'received' | 'cancelled';
    notes: string | null;
    ordered_at: string | null;
    expected_delivery_date: string | null;
    received_at: string | null;
    created_by: number;
    supplier?: Pick<Supplier, 'id' | 'name'> | null;
    items?: PurchaseOrderItem[];
    items_count?: number;
    createdBy?: Pick<User, 'id' | 'name'>;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

// ── Phase 5: Billing & Financials ─────────────────────────────────────────

export interface TaxConfig {
    id: number;
    tenant_id: number;
    name: string;
    rate: number;
    applies_to: 'all' | 'consultation' | 'medicine' | 'procedure' | 'bed';
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface ChargeItem {
    id: number;
    tenant_id: number;
    name: string;
    code: string;
    category: 'consultation' | 'procedure' | 'medicine' | 'bed' | 'lab' | 'radiology' | 'other';
    unit_price: number;
    tax_rate: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface InvoiceLine {
    id: number;
    tenant_id: number;
    invoice_id: number;
    charge_item_id: number | null;
    description: string;
    quantity: number;
    unit_price: number;
    tax_rate: number;
    tax_amount: number;
    discount_amount: number;
    line_total: number;
    reference_type: string | null;
    reference_id: number | null;
    chargeItem?: Pick<ChargeItem, 'id' | 'name' | 'code'> | null;
    created_at: string;
    updated_at: string;
}

export interface Payment {
    id: number;
    tenant_id: number;
    invoice_id: number;
    patient_id: number;
    amount: number;
    payment_method: 'cash' | 'card' | 'bank_transfer' | 'insurance' | 'mobile_money' | 'cheque';
    reference_number: string | null;
    notes: string | null;
    recorded_by: number;
    paid_at: string;
    created_at: string;
    updated_at: string;
}

export interface InsurancePolicy {
    id: number;
    tenant_id: number;
    patient_id: number;
    provider_name: string;
    policy_number: string;
    group_number: string | null;
    coverage_type: string;
    coverage_limit: number | null;
    copay_amount: number;
    deductible_amount: number;
    valid_from: string;
    valid_until: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface Claim {
    id: number;
    tenant_id: number;
    invoice_id: number;
    insurance_policy_id: number;
    patient_id: number;
    claim_number: string;
    status: 'draft' | 'submitted' | 'under_review' | 'approved' | 'partially_approved' | 'rejected' | 'paid';
    amount_claimed: number;
    amount_approved: number | null;
    amount_paid: number | null;
    submitted_at: string | null;
    reviewed_at: string | null;
    notes: string | null;
    submitted_by: number;
    insurance_policy?: InsurancePolicy;
    created_at: string;
    updated_at: string;
}

export interface Invoice {
    id: number;
    tenant_id: number;
    patient_id: number;
    encounter_id: number | null;
    invoice_number: string;
    status: 'draft' | 'sent' | 'paid' | 'partially_paid' | 'cancelled' | 'void';
    subtotal: number;
    tax_total: number;
    discount_amount: number;
    total_amount: number;
    amount_paid: number;
    amount_due: number;
    notes: string | null;
    due_date: string | null;
    paid_at: string | null;
    cancelled_at: string | null;
    pdf_path: string | null;
    created_by: number;
    patient?: Patient;
    encounter?: Pick<Encounter, 'id' | 'encounter_date' | 'encounter_type'> | null;
    lines?: InvoiceLine[];
    payments?: Payment[];
    claims?: Claim[];
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

// ── Phase 4: Beds, Wards, Operating Rooms ─────────────────────────────────

export interface Ward {
    id: number;
    tenant_id: number;
    name: string;
    code: string;
    floor: string | null;
    ward_type: 'general' | 'icu' | 'pediatric' | 'maternity' | 'surgical' | 'oncology' | 'psychiatric';
    is_active: boolean;
    available_beds?: number;
    total_beds?: number;
    beds?: BedWithAllocation[];
    created_at: string;
    updated_at: string;
}

export interface Room {
    id: number;
    tenant_id: number;
    ward_id: number;
    room_number: string;
    room_type: 'general' | 'private' | 'semi_private' | 'icu' | 'isolation';
    is_active: boolean;
    ward?: Pick<Ward, 'id' | 'name'>;
    created_at: string;
    updated_at: string;
}

export interface BedAllocation {
    id: number;
    tenant_id: number;
    bed_id: number;
    patient_id: number;
    encounter_id: number | null;
    allocated_by: number;
    admitted_at: string;
    discharged_at: string | null;
    discharge_reason: string | null;
    notes: string | null;
    patient?: Pick<Patient, 'id' | 'first_name' | 'last_name'>;
    created_at: string;
    updated_at: string;
}

export interface BedWithAllocation {
    id: number;
    tenant_id: number;
    ward_id: number;
    room_id: number | null;
    bed_number: string;
    bed_type: 'standard' | 'icu' | 'pediatric' | 'bariatric' | 'electric';
    status: 'available' | 'occupied' | 'maintenance' | 'reserved' | 'cleaning';
    is_active: boolean;
    ward?: Pick<Ward, 'id' | 'name'>;
    room?: Pick<Room, 'id' | 'room_number'> | null;
    current_allocation: BedAllocation | null;
    created_at: string;
    updated_at: string;
}

export interface WardWithBeds extends Ward {
    beds: BedWithAllocation[];
    available_beds: number;
    total_beds: number;
}

export interface OperatingRoom {
    id: number;
    tenant_id: number;
    name: string;
    room_number: string;
    or_type: 'general' | 'cardiac' | 'ortho' | 'neuro' | 'emergency' | 'obstetric';
    status: 'available' | 'scheduled' | 'in_use' | 'maintenance';
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface OrSchedule {
    id: number;
    tenant_id: number;
    operating_room_id: number;
    encounter_id: number | null;
    surgeon_id: number;
    procedure_name: string;
    scheduled_start: string;
    scheduled_end: string;
    actual_start: string | null;
    actual_end: string | null;
    status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled';
    notes: string | null;
    created_by: number;
    surgeon?: Pick<User, 'id' | 'name'>;
    encounter?: Pick<Encounter, 'id' | 'encounter_date'> | null;
    created_at: string;
    updated_at: string;
}
