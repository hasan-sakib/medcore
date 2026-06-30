import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { StatusBadge } from '@/Components/StatusBadge';
import { Can } from '@/Components/Can';
import type { PageProps, Patient, Appointment, Encounter } from '@/types';

interface Props extends PageProps {
    patient: Patient;
    appointments: Appointment[];
    encounters: Encounter[];
}

type Tab = 'demographics' | 'encounters' | 'appointments';

export default function Show({ patient, appointments, encounters }: Props) {
    const [tab, setTab] = useState<Tab>('demographics');

    const tabClass = (t: Tab) =>
        `px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors ${
            tab === t
                ? 'border-primary-600 text-primary-600'
                : 'border-transparent text-gray-500 hover:text-gray-700'
        }`;

    return (
        <AppLayout>
            <Head title={`${patient.first_name} ${patient.last_name}`} />
            <div className="max-w-4xl space-y-4">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-gray-900">
                            {patient.first_name} {patient.last_name}
                        </h1>
                        <p className="text-sm text-gray-500 font-mono">{patient.mrn}</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <StatusBadge status={patient.status} />
                        <Can permission="patients.edit">
                            <Link
                                href={`/patients/${patient.id}/edit`}
                                className="rounded border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50"
                            >
                                Edit
                            </Link>
                        </Can>
                        <Can permission="appointments.create">
                            <Link
                                href={`/appointments/create?patient_id=${patient.id}`}
                                className="rounded bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700"
                            >
                                Book Appointment
                            </Link>
                        </Can>
                    </div>
                </div>

                {/* Tabs */}
                <div className="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <div className="flex border-b border-gray-200 px-4">
                        <button className={tabClass('demographics')} onClick={() => setTab('demographics')}>Demographics</button>
                        <Can permission="encounters.view">
                            <button className={tabClass('encounters')} onClick={() => setTab('encounters')}>
                                Encounters ({encounters.length})
                            </button>
                        </Can>
                        <Can permission="appointments.view">
                            <button className={tabClass('appointments')} onClick={() => setTab('appointments')}>
                                Appointments ({appointments.length})
                            </button>
                        </Can>
                    </div>

                    <div className="p-6">
                        {tab === 'demographics' && (
                            <dl className="grid grid-cols-2 gap-x-8 gap-y-4 text-sm">
                                {([
                                    ['Date of Birth', patient.date_of_birth],
                                    ['Gender', patient.gender],
                                    ['Phone', patient.phone],
                                    ['Email', patient.email],
                                    ['National ID', patient.national_id],
                                    ['Blood Group', patient.blood_group],
                                    ['Address', patient.address],
                                    ['Emergency Contact', patient.emergency_contact],
                                    ['Department', patient.department?.name],
                                ] as [string, string | null | undefined][]).map(([label, value]) => (
                                    <div key={label}>
                                        <dt className="font-medium text-gray-500">{label}</dt>
                                        <dd className="text-gray-900 mt-0.5">{value ?? '—'}</dd>
                                    </div>
                                ))}
                            </dl>
                        )}

                        {tab === 'encounters' && (
                            <div className="space-y-2">
                                {encounters.length === 0 && <p className="text-sm text-gray-400">No encounters yet</p>}
                                {encounters.map(enc => (
                                    <Link
                                        key={enc.id}
                                        href={`/encounters/${enc.id}`}
                                        className="flex items-center justify-between p-3 rounded border border-gray-100 hover:bg-gray-50"
                                    >
                                        <div>
                                            <p className="text-sm font-medium text-gray-900">{enc.encounter_date}</p>
                                            <p className="text-xs text-gray-500">{enc.chief_complaint ?? 'No chief complaint'}</p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <StatusBadge status={enc.encounter_type} />
                                            <StatusBadge status={enc.status} />
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        )}

                        {tab === 'appointments' && (
                            <div className="space-y-2">
                                {appointments.length === 0 && <p className="text-sm text-gray-400">No appointments yet</p>}
                                {appointments.map(appt => (
                                    <Link
                                        key={appt.id}
                                        href={`/appointments/${appt.id}`}
                                        className="flex items-center justify-between p-3 rounded border border-gray-100 hover:bg-gray-50"
                                    >
                                        <div>
                                            <p className="text-sm font-medium text-gray-900">
                                                {new Date(appt.scheduled_at).toLocaleString()}
                                            </p>
                                            <p className="text-xs text-gray-500">{appt.doctor?.name ?? 'Doctor'}</p>
                                        </div>
                                        <StatusBadge status={appt.status} />
                                    </Link>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
