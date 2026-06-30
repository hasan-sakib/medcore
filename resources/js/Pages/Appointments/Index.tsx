import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Pagination } from '@/Components/Pagination';
import { StatusBadge } from '@/Components/StatusBadge';
import { Can } from '@/Components/Can';
import type { PageProps, PaginatedResource, Appointment } from '@/types';

interface Props extends PageProps {
    appointments: PaginatedResource<Appointment>;
    filters: { date?: string; doctor_id?: string };
}

export default function Index({ appointments, filters }: Props) {
    const handleFilter = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const data = new FormData(e.currentTarget);
        router.get('/appointments', Object.fromEntries(data), { preserveState: true, replace: true });
    };

    return (
        <AppLayout>
            <Head title="Appointments" />
            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold text-gray-900">Appointments</h1>
                    <Can permission="appointments.create">
                        <Link
                            href="/appointments/create"
                            className="rounded bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700"
                        >
                            Book Appointment
                        </Link>
                    </Can>
                </div>

                <form onSubmit={handleFilter} className="flex gap-2">
                    <input
                        type="date"
                        name="date"
                        defaultValue={filters.date ?? ''}
                        className="rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                    />
                    <button type="submit" className="rounded bg-gray-100 px-3 py-2 text-sm hover:bg-gray-200">
                        Filter
                    </button>
                    {(filters.date) && (
                        <Link href="/appointments" className="rounded border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50">
                            Clear
                        </Link>
                    )}
                </form>

                <div className="rounded-lg border border-gray-200 bg-white overflow-hidden">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">Patient</th>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">Doctor</th>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">Scheduled</th>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                                <th className="px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {appointments.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-4 py-8 text-center text-gray-400">
                                        No appointments found
                                    </td>
                                </tr>
                            )}
                            {appointments.data.map(a => (
                                <tr key={a.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3">
                                        <p className="font-medium text-gray-900">
                                            {a.patient ? `${a.patient.first_name} ${a.patient.last_name}` : '—'}
                                        </p>
                                        <p className="text-xs text-gray-500">{a.patient?.mrn}</p>
                                    </td>
                                    <td className="px-4 py-3 text-gray-700">{a.doctor?.name ?? '—'}</td>
                                    <td className="px-4 py-3 text-gray-700">
                                        {new Date(a.scheduled_at).toLocaleString()}
                                    </td>
                                    <td className="px-4 py-3"><StatusBadge status={a.status} /></td>
                                    <td className="px-4 py-3 text-right">
                                        <Link href={`/appointments/${a.id}`} className="text-primary-600 hover:underline text-xs">
                                            View
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <Pagination data={appointments} />
            </div>
        </AppLayout>
    );
}
