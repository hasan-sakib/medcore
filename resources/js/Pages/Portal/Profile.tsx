import { Head, useForm } from '@inertiajs/react';
import PortalLayout from '@/Layouts/PortalLayout';
import type { PageProps, Patient } from '@/types';

interface Props extends PageProps {
    patient: Patient;
}

export default function PortalProfile({ patient }: Props) {
    const form = useForm({
        phone:   patient.phone ?? '',
        email:   patient.email ?? '',
        address: patient.address ?? '',
    });

    const GENDER_LABEL: Record<string, string> = { male: 'Male', female: 'Female', other: 'Other' };

    return (
        <PortalLayout>
            <Head title="My Profile" />

            <div className="max-w-xl space-y-6">
                <h1 className="text-xl font-semibold text-gray-900">My Profile</h1>

                {/* Read-only demographics */}
                <div className="bg-white rounded-xl border border-gray-200 p-5">
                    <h2 className="font-semibold text-gray-900 text-sm mb-3">Personal Information</h2>
                    <dl className="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                        {[
                            ['Full Name', `${patient.first_name} ${patient.last_name}`],
                            ['MRN', patient.mrn],
                            ['Date of Birth', patient.date_of_birth],
                            ['Gender', patient.gender ? GENDER_LABEL[patient.gender] : '—'],
                            ['Blood Group', patient.blood_group ?? '—'],
                            ['National ID', patient.national_id ?? '—'],
                        ].map(([label, value]) => (
                            <div key={label as string}>
                                <dt className="text-xs text-gray-400 uppercase tracking-wide">{label}</dt>
                                <dd className="font-medium text-gray-900 mt-0.5">{value || '—'}</dd>
                            </div>
                        ))}
                    </dl>
                    <p className="text-xs text-gray-400 mt-4">Personal information can only be updated by clinic staff.</p>
                </div>

                {/* Editable contact info */}
                <div className="bg-white rounded-xl border border-gray-200 p-5">
                    <h2 className="font-semibold text-gray-900 text-sm mb-3">Contact Information</h2>
                    <div className="space-y-3">
                        <div>
                            <label className="form-label">Phone</label>
                            <input
                                type="tel"
                                className="form-input"
                                value={form.data.phone}
                                onChange={e => form.setData('phone', e.target.value)}
                            />
                            {form.errors.phone && <p className="form-error">{form.errors.phone}</p>}
                        </div>
                        <div>
                            <label className="form-label">Email</label>
                            <input
                                type="email"
                                className="form-input"
                                value={form.data.email}
                                onChange={e => form.setData('email', e.target.value)}
                            />
                            {form.errors.email && <p className="form-error">{form.errors.email}</p>}
                        </div>
                        <div>
                            <label className="form-label">Address</label>
                            <textarea
                                className="form-input"
                                rows={2}
                                value={form.data.address}
                                onChange={e => form.setData('address', e.target.value)}
                            />
                        </div>
                        <button
                            type="button"
                            disabled={form.processing}
                            onClick={() => form.patch('/portal/profile')}
                            className="rounded-lg bg-emerald-600 text-white font-medium px-4 py-2 text-sm hover:bg-emerald-700 disabled:opacity-50 transition-colors"
                        >
                            Save Changes
                        </button>
                    </div>
                </div>

                {/* Emergency contact read-only */}
                {patient.emergency_contact && (
                    <div className="bg-white rounded-xl border border-gray-200 p-5">
                        <h2 className="font-semibold text-gray-900 text-sm mb-2">Emergency Contact</h2>
                        <p className="text-sm text-gray-700">{patient.emergency_contact}</p>
                    </div>
                )}
            </div>
        </PortalLayout>
    );
}
