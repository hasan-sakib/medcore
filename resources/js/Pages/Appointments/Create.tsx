import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { PatientSearchInput } from '@/Components/PatientSearchInput';
import { AppointmentSlotPicker } from '@/Components/AppointmentSlotPicker';
import type { PageProps, Department, AppointmentSlot, Patient } from '@/types';

interface DoctorOption {
    id: number;
    name: string;
}

interface Props extends PageProps {
    departments: Department[];
    doctors: DoctorOption[];
    preselectedPatientId?: number;
    preselectedPatientName?: string;
}

export default function Create({ departments, doctors, preselectedPatientId, preselectedPatientName }: Props) {
    const [selectedDate, setSelectedDate] = useState('');
    const [patientLabel, setPatientLabel] = useState(preselectedPatientName ?? '');

    const form = useForm({
        patient_id: preselectedPatientId ? String(preselectedPatientId) : '',
        doctor_id: '',
        department_id: '',
        scheduled_at: '',
        ends_at: '',
        reason: '',
    });

    const handlePatientSelect = (patient: Patient) => {
        form.setData('patient_id', String(patient.id));
        setPatientLabel(`${patient.first_name} ${patient.last_name} (${patient.mrn})`);
    };

    const handleSlotSelect = (slot: AppointmentSlot) => {
        form.setData('scheduled_at', slot.start);
        form.setData('ends_at', slot.end);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/appointments');
    };

    return (
        <AppLayout>
            <Head title="Book Appointment" />
            <div className="max-w-2xl">
                <h1 className="text-xl font-semibold text-gray-900 mb-6">Book Appointment</h1>

                <form onSubmit={submit} className="bg-white rounded-lg border border-gray-200 p-6 space-y-5">
                    {/* Patient */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Patient <span className="text-danger-500">*</span>
                        </label>
                        {preselectedPatientId ? (
                            <div className="rounded border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                                {patientLabel}
                            </div>
                        ) : (
                            <PatientSearchInput onSelect={handlePatientSelect} />
                        )}
                        {form.errors.patient_id && <p className="mt-1 text-xs text-danger-600">{form.errors.patient_id}</p>}
                    </div>

                    {/* Doctor */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Doctor <span className="text-danger-500">*</span>
                        </label>
                        <select
                            value={form.data.doctor_id}
                            onChange={e => form.setData('doctor_id', e.target.value)}
                            className="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                        >
                            <option value="">Select doctor…</option>
                            {doctors.map(d => (
                                <option key={d.id} value={d.id}>{d.name}</option>
                            ))}
                        </select>
                        {form.errors.doctor_id && <p className="mt-1 text-xs text-danger-600">{form.errors.doctor_id}</p>}
                    </div>

                    {/* Department */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Department</label>
                        <select
                            value={form.data.department_id}
                            onChange={e => form.setData('department_id', e.target.value)}
                            className="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                        >
                            <option value="">Select…</option>
                            {departments.map(d => (
                                <option key={d.id} value={d.id}>{d.name}</option>
                            ))}
                        </select>
                    </div>

                    {/* Date + Slot Picker */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Date <span className="text-danger-500">*</span>
                        </label>
                        <input
                            type="date"
                            value={selectedDate}
                            onChange={e => { setSelectedDate(e.target.value); form.setData('scheduled_at', ''); }}
                            className="rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                        />
                        {form.errors.scheduled_at && <p className="mt-1 text-xs text-danger-600">{form.errors.scheduled_at}</p>}
                    </div>

                    <AppointmentSlotPicker
                        doctorId={form.data.doctor_id ? Number(form.data.doctor_id) : null}
                        date={selectedDate}
                        selected={form.data.scheduled_at}
                        onSelect={handleSlotSelect}
                    />

                    {form.data.scheduled_at && (
                        <p className="text-sm text-clinical-700 bg-clinical-50 rounded px-3 py-2">
                            Selected: {new Date(form.data.scheduled_at).toLocaleString()}
                        </p>
                    )}

                    {/* Reason */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                        <textarea
                            value={form.data.reason}
                            onChange={e => form.setData('reason', e.target.value)}
                            rows={2}
                            className="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                        />
                    </div>

                    <div className="flex gap-3 pt-2">
                        <button
                            type="submit"
                            disabled={form.processing || !form.data.patient_id || !form.data.scheduled_at}
                            className="rounded bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-60"
                        >
                            {form.processing ? 'Booking…' : 'Book Appointment'}
                        </button>
                        <a href="/appointments" className="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
