import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Pagination } from '@/Components/Pagination';
import { Can } from '@/Components/Can';
import { StatusBadge } from '@/Components/StatusBadge';
import type { PageProps, PaginatedResource, Prescription } from '@/types';

interface Props extends PageProps {
    prescriptions: PaginatedResource<Prescription>;
    filters: { status?: string; patient_id?: string };
}

export default function Index({ prescriptions, filters }: Props) {
    const handleFilter = (e: React.FormEvent<HTMLSelectElement>) => {
        router.get('/prescriptions', { status: e.currentTarget.value }, { preserveState: true, replace: true });
    };

    return (
        <AppLayout>
            <Head title="Prescriptions" />
            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold text-gray-900">Prescriptions</h1>
                    <Can permission="prescriptions.create">
                        <Link
                            href="/prescriptions/create"
                            className="rounded bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700"
                        >
                            New Prescription
                        </Link>
                    </Can>
                </div>

                <div className="flex gap-2">
                    <select
                        defaultValue={filters.status ?? ''}
                        onChange={handleFilter}
                        className="rounded border border-gray-300 px-2 py-1.5 text-sm"
                    >
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="partially_filled">Partially Filled</option>
                        <option value="filled">Filled</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div className="overflow-x-auto rounded-lg border bg-white">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left text-xs text-gray-500">
                            <tr>
                                <th className="px-4 py-2">Patient</th>
                                <th className="px-4 py-2">Prescribed By</th>
                                <th className="px-4 py-2">Prescribed At</th>
                                <th className="px-4 py-2">Items</th>
                                <th className="px-4 py-2">Status</th>
                                <th className="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {prescriptions.data.map((rx) => (
                                <tr key={rx.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-2">
                                        {rx.patient?.first_name} {rx.patient?.last_name}
                                        <span className="block text-xs text-gray-400">{rx.patient?.mrn}</span>
                                    </td>
                                    <td className="px-4 py-2">{rx.prescribedBy?.name}</td>
                                    <td className="px-4 py-2 text-gray-500">{rx.prescribed_at.slice(0, 10)}</td>
                                    <td className="px-4 py-2">{rx.items?.length ?? 0}</td>
                                    <td className="px-4 py-2"><StatusBadge status={rx.status} /></td>
                                    <td className="px-4 py-2">
                                        <Link href={`/prescriptions/${rx.id}`} className="text-xs text-primary-600 hover:underline">View</Link>
                                    </td>
                                </tr>
                            ))}
                            {prescriptions.data.length === 0 && (
                                <tr><td colSpan={6} className="px-4 py-8 text-center text-gray-400">No prescriptions found.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination data={prescriptions} />
            </div>
        </AppLayout>
    );
}
