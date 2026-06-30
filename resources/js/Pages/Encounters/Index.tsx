import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Pagination } from '@/Components/Pagination';
import { StatusBadge } from '@/Components/StatusBadge';
import type { PageProps, PaginatedResource, Encounter } from '@/types';

interface Props extends PageProps {
    encounters: PaginatedResource<Encounter>;
    filters: { status?: string; date?: string };
}

export default function Index({ encounters, filters }: Props) {
    const handleFilter = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const data = new FormData(e.currentTarget);
        router.get('/encounters', Object.fromEntries(data), { preserveState: true, replace: true });
    };

    return (
        <AppLayout>
            <Head title="Encounters" />
            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold text-gray-900">Encounters</h1>
                </div>

                <form onSubmit={handleFilter} className="flex gap-2 flex-wrap">
                    <select
                        name="status"
                        defaultValue={filters.status ?? ''}
                        className="rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                    >
                        <option value="">All statuses</option>
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <input
                        type="date"
                        name="date"
                        defaultValue={filters.date ?? ''}
                        className="rounded border border-gray-300 px-3 py-2 text-sm"
                    />
                    <button type="submit" className="rounded bg-gray-100 px-3 py-2 text-sm hover:bg-gray-200">
                        Filter
                    </button>
                    {(filters.status || filters.date) && (
                        <Link href="/encounters" className="rounded border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50">
                            Clear
                        </Link>
                    )}
                </form>

                <div className="rounded-lg border border-gray-200 bg-white overflow-hidden">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">Patient</th>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">Date</th>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">Doctor</th>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">Type</th>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                                <th className="px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {encounters.data.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-gray-400">
                                        No encounters found
                                    </td>
                                </tr>
                            )}
                            {encounters.data.map(enc => (
                                <tr key={enc.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3">
                                        <p className="font-medium text-gray-900">
                                            {enc.patient ? `${enc.patient.first_name} ${enc.patient.last_name}` : '—'}
                                        </p>
                                        <p className="text-xs text-gray-500">{enc.patient?.mrn}</p>
                                    </td>
                                    <td className="px-4 py-3 text-gray-700">{enc.encounter_date}</td>
                                    <td className="px-4 py-3 text-gray-700">{enc.attendingDoctor?.name ?? '—'}</td>
                                    <td className="px-4 py-3"><StatusBadge status={enc.encounter_type} /></td>
                                    <td className="px-4 py-3"><StatusBadge status={enc.status} /></td>
                                    <td className="px-4 py-3 text-right">
                                        <Link href={`/encounters/${enc.id}`} className="text-primary-600 hover:underline text-xs">
                                            Open
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <Pagination data={encounters} />
            </div>
        </AppLayout>
    );
}
