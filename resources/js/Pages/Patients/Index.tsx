import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Pagination } from '@/Components/Pagination';
import { StatusBadge } from '@/Components/StatusBadge';
import { Can } from '@/Components/Can';
import type { PageProps, PaginatedResource, Patient } from '@/types';

interface Props extends PageProps {
    patients: PaginatedResource<Patient>;
    filters: { q?: string };
}

export default function Index({ patients, filters }: Props) {
    const handleSearch = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const q = (e.currentTarget.elements.namedItem('q') as HTMLInputElement).value;
        router.get('/patients', { q }, { preserveState: true, replace: true });
    };

    return (
        <AppLayout>
            <Head title="Patients" />
            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold text-gray-900">Patients</h1>
                    <Can permission="patients.create">
                        <Link
                            href="/patients/create"
                            className="rounded bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700"
                        >
                            Register Patient
                        </Link>
                    </Can>
                </div>

                <form onSubmit={handleSearch} className="flex gap-2">
                    <input
                        type="text"
                        name="q"
                        defaultValue={filters.q ?? ''}
                        placeholder="Search by name, MRN, phone…"
                        className="flex-1 rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                    />
                    <button type="submit" className="rounded bg-gray-100 px-3 py-2 text-sm hover:bg-gray-200">
                        Search
                    </button>
                </form>

                <div className="rounded-lg border border-gray-200 bg-white overflow-hidden">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">MRN</th>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">Name</th>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">DOB</th>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">Phone</th>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                                <th className="px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {patients.data.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-gray-400">
                                        No patients found
                                    </td>
                                </tr>
                            )}
                            {patients.data.map(p => (
                                <tr key={p.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3 font-mono text-xs text-gray-600">{p.mrn}</td>
                                    <td className="px-4 py-3 font-medium text-gray-900">
                                        {p.first_name} {p.last_name}
                                    </td>
                                    <td className="px-4 py-3 text-gray-600">{p.date_of_birth}</td>
                                    <td className="px-4 py-3 text-gray-600">{p.phone ?? '—'}</td>
                                    <td className="px-4 py-3"><StatusBadge status={p.status} /></td>
                                    <td className="px-4 py-3 text-right">
                                        <Link href={`/patients/${p.id}`} className="text-primary-600 hover:underline text-xs">
                                            View
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <Pagination data={patients} />
            </div>
        </AppLayout>
    );
}
