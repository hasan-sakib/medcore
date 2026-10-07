import { Head, Link, router, useForm } from '@inertiajs/react';
import PortalLayout from '@/Layouts/PortalLayout';
import type { PageProps, PaginatedResource, Appointment } from '@/types';

type ApptRow = Appointment & {
    doctor?: { name: string };
    department?: { name: string } | null;
};

interface Props extends PageProps {
    appointments: PaginatedResource<ApptRow>;
}

const STATUS_CHIP: Record<string, string> = {
    pending:    'bg-yellow-100 text-yellow-700',
    confirmed:  'bg-green-100 text-green-700',
    checked_in: 'bg-blue-100 text-blue-700',
    completed:  'bg-gray-100 text-gray-600',
    cancelled:  'bg-red-100 text-red-600',
    no_show:    'bg-orange-100 text-orange-700',
};

export default function PortalAppointmentsIndex({ appointments }: Props) {
    const cancelForm = useForm({});

    const handleCancel = (id: number) => {
        if (!confirm('Cancel this appointment?')) return;
        cancelForm.patch(`/portal/appointments/${id}/cancel`, { preserveScroll: true });
    };

    return (
        <PortalLayout>
            <Head title="My Appointments" />

            <div className="space-y-5">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold text-gray-900">My Appointments</h1>
                    <Link href="/portal/appointments/book" className="btn-primary text-sm" style={{ background: '#059669' }}>
                        + Book Appointment
                    </Link>
                </div>

                <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                {['Date & Time', 'Doctor', 'Department', 'Reason', 'Status', ''].map(h => (
                                    <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">{h}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100 text-sm">
                            {appointments.data.map(appt => (
                                <tr key={appt.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3">
                                        {new Date(appt.scheduled_at).toLocaleString([], {
                                            weekday: 'short', month: 'short', day: 'numeric',
                                            hour: '2-digit', minute: '2-digit',
                                        })}
                                    </td>
                                    <td className="px-4 py-3">
                                        {appt.doctor ? `Dr. ${appt.doctor.name}` : '—'}
                                    </td>
                                    <td className="px-4 py-3 text-gray-500">{appt.department?.name ?? '—'}</td>
                                    <td className="px-4 py-3 text-gray-500 max-w-[200px] truncate">{appt.reason ?? '—'}</td>
                                    <td className="px-4 py-3">
                                        <span className={`text-xs px-2 py-0.5 rounded-full capitalize font-medium ${STATUS_CHIP[appt.status] ?? ''}`}>
                                            {appt.status.replace('_', ' ')}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3">
                                        {['pending', 'confirmed'].includes(appt.status) && (
                                            <button
                                                onClick={() => handleCancel(appt.id)}
                                                className="text-xs text-red-500 hover:underline"
                                            >
                                                Cancel
                                            </button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {appointments.data.length === 0 && (
                        <p className="text-center py-10 text-sm text-gray-400">
                            No appointments yet. <Link href="/portal/appointments/book" className="text-emerald-600 hover:underline">Book one now</Link>
                        </p>
                    )}
                </div>

                {appointments.last_page > 1 && (
                    <div className="flex justify-center gap-1">
                        {appointments.links.map((link, i) => (
                            <button
                                key={i}
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url)}
                                className={`px-3 py-1.5 text-xs rounded ${link.active ? 'bg-emerald-600 text-white' : 'bg-white border border-gray-300 text-gray-600 disabled:opacity-40'}`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </PortalLayout>
    );
}
