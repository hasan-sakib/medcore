import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Pagination } from '@/Components/Pagination';
import { Can } from '@/Components/Can';
import StockLevelBadge from '@/Components/StockLevelBadge';
import type { Medicine, PageProps, PaginatedResource } from '@/types';

interface Props extends PageProps {
    medicines: PaginatedResource<Medicine & { stock_on_hand: number }>;
    filters: { search?: string; category?: string };
}

export default function Index({ medicines, filters }: Props) {
    const handleSearch = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const search = (e.currentTarget.elements.namedItem('search') as HTMLInputElement).value;
        router.get('/medicines', { search }, { preserveState: true, replace: true });
    };

    return (
        <AppLayout>
            <Head title="Medicines" />
            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold text-gray-900">Medicine Catalog</h1>
                    <Can permission="medicines.create">
                        <Link
                            href="/admin/medicines/create"
                            className="rounded bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700"
                        >
                            Add Medicine
                        </Link>
                    </Can>
                </div>

                <form onSubmit={handleSearch} className="flex gap-2">
                    <input
                        type="text"
                        name="search"
                        defaultValue={filters.search ?? ''}
                        placeholder="Search by name or SKU…"
                        className="rounded border border-gray-300 px-3 py-1.5 text-sm focus:border-primary-500 focus:outline-none"
                    />
                    <button type="submit" className="rounded bg-gray-100 px-3 py-1.5 text-sm hover:bg-gray-200">Search</button>
                </form>

                <div className="overflow-x-auto rounded-lg border bg-white">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left text-xs text-gray-500">
                            <tr>
                                <th className="px-4 py-2">Name</th>
                                <th className="px-4 py-2">SKU</th>
                                <th className="px-4 py-2">Category</th>
                                <th className="px-4 py-2">Strength</th>
                                <th className="px-4 py-2">Unit</th>
                                <th className="px-4 py-2">Stock</th>
                                <th className="px-4 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {medicines.data.map((med) => (
                                <tr key={med.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-2 font-medium">
                                        <Link href={`/medicines/${med.id}`} className="text-primary-600 hover:underline">{med.name}</Link>
                                        {med.generic_name && <span className="block text-xs text-gray-400">{med.generic_name}</span>}
                                    </td>
                                    <td className="px-4 py-2 font-mono text-xs">{med.sku}</td>
                                    <td className="px-4 py-2 text-gray-500">{med.category ?? '—'}</td>
                                    <td className="px-4 py-2">{med.strength ?? '—'}</td>
                                    <td className="px-4 py-2 capitalize">{med.unit_type}</td>
                                    <td className="px-4 py-2"><StockLevelBadge medicine={med} /></td>
                                    <td className="px-4 py-2">
                                        <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${med.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}`}>
                                            {med.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </td>
                                </tr>
                            ))}
                            {medicines.data.length === 0 && (
                                <tr><td colSpan={7} className="px-4 py-8 text-center text-gray-400">No medicines found.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination data={medicines} />
            </div>
        </AppLayout>
    );
}
