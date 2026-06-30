import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { StatusBadge } from '@/Components/StatusBadge';
import { Can } from '@/Components/Can';
import type { PageProps, Appointment } from '@/types';

interface Props extends PageProps {
    appointment: Appointment;
}

export default function Show({ appointment: a }: Props) {
    return (
        <AppLayout>
            <Head title="Appointment" />
            <div className="max-w-2xl space-y-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold text-gray-900">Appointment</h1>
                    <div className="flex items-center gap-2">
                        <StatusBadge status={a.status} />
                        <Can permission="appointments.edit">
                            <Link
                                href={`/appointments/${a.id}/edit`}
                                className="rounded border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50"
                            >
                                Edit
                            </Link>
                        </Can>
                    </div>
                </div>

                <div className="bg-white rounded-lg border border-gray-200 p-6">
                    <dl className="grid grid-cols-2 gap-x-8 gap-y-4 text-sm">
                        <div className="col-span-2">
                            <dt className="font-medium text-gray-500">Patient</dt>
                            <dd className="mt-0.5">
                                {a.patient ? (
                                    <Link href={`/patients/${a.patient_id}`} className="text-primary-600 hover:underline">
                                        {a.patient.first_name} {a.patient.last_name}
                                        <span className="text-gray-400 ml-2 font-mono text-xs">{a.patient.mrn}</span>
                                    </Link>
                                ) : '—'}
                            </dd>
                        </div>
                        <div>
                            <dt className="font-medium text-gray-500">Doctor</dt>
                            <dd className="mt-0.5">{a.doctor?.name ?? '—'}</dd>
                        </div>
                        <div>
                            <dt className="font-medium text-gray-500">Department</dt>
                            <dd className="mt-0.5">{a.department?.name ?? '—'}</dd>
                        </div>
                        <div>
                            <dt className="font-medium text-gray-500">Scheduled</dt>
                            <dd className="mt-0.5">{new Date(a.scheduled_at).toLocaleString()}</dd>
                        </div>
                        <div>
                            <dt className="font-medium text-gray-500">Ends</dt>
                            <dd className="mt-0.5">{new Date(a.ends_at).toLocaleString()}</dd>
                        </div>
                        {a.reason && (
                            <div className="col-span-2">
                                <dt className="font-medium text-gray-500">Reason</dt>
                                <dd className="mt-0.5">{a.reason}</dd>
                            </div>
                        )}
                        {a.cancellation_reason && (
                            <div className="col-span-2">
                                <dt className="font-medium text-gray-500">Cancellation Reason</dt>
                                <dd className="mt-0.5 text-gray-600">{a.cancellation_reason}</dd>
                            </div>
                        )}
                    </dl>
                </div>

                {a.encounter && (
                    <Link
                        href={`/encounters/${a.encounter.id}`}
                        className="block bg-clinical-50 border border-clinical-200 rounded-lg px-4 py-3 text-sm text-clinical-700 hover:bg-clinical-100"
                    >
                        View linked encounter →
                    </Link>
                )}

                {!a.encounter && a.status === 'confirmed' && (
                    <Can permission="encounters.create">
                        <Link
                            href={`/encounters?appointment_id=${a.id}`}
                            className="block bg-primary-50 border border-primary-200 rounded-lg px-4 py-3 text-sm text-primary-700 hover:bg-primary-100"
                        >
                            Start encounter (check in patient) →
                        </Link>
                    </Can>
                )}
            </div>
        </AppLayout>
    );
}
