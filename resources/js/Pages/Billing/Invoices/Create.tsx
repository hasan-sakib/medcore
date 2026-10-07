import { Head, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps, Patient, Encounter } from '@/types';

interface Props extends PageProps {
    patients: Pick<Patient, 'id' | 'first_name' | 'last_name' | 'mrn'>[];
    encounters: Pick<Encounter, 'id' | 'encounter_date' | 'encounter_type' | 'chief_complaint'>[];
}

export default function CreateInvoice({ patients, encounters }: Props) {
    const form = useForm({
        patient_id:    '',
        encounter_id:  '',
        from_encounter: false,
    });

    const handlePatientChange = (patientId: string) => {
        form.setData('patient_id', patientId);
        form.setData('encounter_id', '');
        if (patientId) {
            router.reload({ data: { patient_id: patientId }, only: ['encounters'], preserveState: true });
        }
    };

    return (
        <AppLayout>
            <Head title="New Invoice" />

            <div className="max-w-lg space-y-6">
                <h1 className="text-xl font-semibold text-gray-900">New Invoice</h1>

                <div className="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <div>
                        <label className="form-label">Patient</label>
                        <select
                            className="form-input"
                            value={form.data.patient_id}
                            onChange={e => handlePatientChange(e.target.value)}
                            required
                        >
                            <option value="">Select patient…</option>
                            {patients.map(p => (
                                <option key={p.id} value={p.id}>
                                    {p.first_name} {p.last_name} ({p.mrn})
                                </option>
                            ))}
                        </select>
                        {form.errors.patient_id && <p className="form-error">{form.errors.patient_id}</p>}
                    </div>

                    {encounters.length > 0 && (
                        <div>
                            <label className="form-label">Link to Encounter (optional)</label>
                            <select
                                className="form-input"
                                value={form.data.encounter_id}
                                onChange={e => form.setData('encounter_id', e.target.value)}
                            >
                                <option value="">— No encounter —</option>
                                {encounters.map(enc => (
                                    <option key={enc.id} value={enc.id}>
                                        #{enc.id} · {enc.encounter_date} · {enc.encounter_type}
                                        {enc.chief_complaint ? ` · ${enc.chief_complaint}` : ''}
                                    </option>
                                ))}
                            </select>

                            {form.data.encounter_id && (
                                <label className="flex items-center gap-2 mt-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={form.data.from_encounter}
                                        onChange={e => form.setData('from_encounter', e.target.checked)}
                                        className="rounded border-gray-300"
                                    />
                                    <span className="text-sm text-gray-700">Auto-populate charges from this encounter</span>
                                </label>
                            )}
                        </div>
                    )}

                    <div className="flex gap-3 pt-2">
                        <button
                            type="button"
                            disabled={!form.data.patient_id || form.processing}
                            onClick={() => form.post('/invoices')}
                            className="btn-primary"
                        >
                            Create Invoice
                        </button>
                        <a href="/invoices" className="btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
