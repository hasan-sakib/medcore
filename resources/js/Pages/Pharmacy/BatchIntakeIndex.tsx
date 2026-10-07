import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Pagination } from '@/Components/Pagination';
import { Can } from '@/Components/Can';
import ExpiryBadge from '@/Components/ExpiryBadge';
import type { Medicine, MedicineBatch, PageProps, PaginatedResource } from '@/types';

interface Props extends PageProps {
    batches: PaginatedResource<MedicineBatch>;
    medicines: Array<Pick<Medicine, 'id' | 'name' | 'sku'>>;
    filters: { medicine_id?: string; status?: string };
}

const STATUS_STYLES: Record<string, string> = {
    active: 'bg-green-100 text-green-700',
    expired: 'bg-gray-100 text-gray-600',
    quarantined: 'bg-amber-100 text-amber-700',
    depleted: 'bg-gray-100 text-gray-500',
};

export default function BatchIntakeIndex({ batches, medicines, filters }: Props) {
    const applyFilter = (key: 'medicine_id' | 'status', value: string) => {
        const next = { ...filters, [key]: value };
        const params = Object.fromEntries(Object.entries(next).filter(([, v]) => v));
        router.get('/medicine-batches', params, { preserveState: true, replace: true });
    };

    return (
        <AppLayout>
            <Head title="Medicine Batches" />
            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-gray-900">Medicine Batches</h1>
                        <p className="mt-1 text-sm text-gray-500">{batches.total} batches, ordered by expiry date</p>
                    </div>
                    <Can permission="medicine-batches.create">
                        <Link href="/medicine-batches/create" className="btn-primary">Receive stock</Link>
                    </Can>
                </div>

                <div className="flex flex-wrap gap-2">
                    <select
                        value={filters.medicine_id ?? ''}
                        onChange={(e) => applyFilter('medicine_id', e.target.value)}
                        className="rounded border border-gray-300 px-2 py-1.5 text-sm"
                    >
                        <option value="">All medicines</option>
                        {medicines.map((m) => (
                            <option key={m.id} value={m.id}>{m.name} ({m.sku})</option>
                        ))}
                    </select>
                    <select
                        value={filters.status ?? ''}
                        onChange={(e) => applyFilter('status', e.target.value)}
                        className="rounded border border-gray-300 px-2 py-1.5 text-sm"
                    >
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="expired">Expired</option>
                        <option value="quarantined">Quarantined</option>
                        <option value="depleted">Depleted</option>
                    </select>
                </div>

                <div className="card overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50 text-left text-xs text-gray-500">
                            <tr>
                                <th className="px-4 py-2">Medicine</th>
                                <th className="px-4 py-2">Batch no.</th>
                                <th className="px-4 py-2">Supplier</th>
                                <th className="px-4 py-2">Expiry</th>
                                <th className="px-4 py-2">Received</th>
                                <th className="px-4 py-2">On hand</th>
                                <th className="px-4 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y bg-white">
                            {batches.data.map((b) => (
                                <tr key={b.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-2 font-medium">
                                        {b.medicine ? (
                                            <Link href={`/medicines/${b.medicine.id}`} className="text-primary-600 hover:underline">{b.medicine.name}</Link>
                                        ) : '—'}
                                        {b.medicine && <span className="block font-mono text-xs text-gray-400">{b.medicine.sku}</span>}
                                    </td>
                                    <td className="px-4 py-2 font-mono text-xs">{b.batch_number}</td>
                                    <td className="px-4 py-2 text-gray-500">{b.supplier?.name ?? '—'}</td>
                                    <td className="px-4 py-2"><ExpiryBadge expiryDate={b.expiry_date.slice(0, 10)} /></td>
                                    <td className="px-4 py-2 text-gray-500">{b.quantity_received}</td>
                                    <td className="px-4 py-2 font-medium">{b.quantity_on_hand}</td>
                                    <td className="px-4 py-2">
                                        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${STATUS_STYLES[b.status] ?? 'bg-gray-100 text-gray-600'}`}>
                                            {b.status}
                                        </span>
                                    </td>
                                </tr>
                            ))}
                            {batches.data.length === 0 && (
                                <tr><td colSpan={7} className="px-4 py-8 text-center text-gray-400">No batches found.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination data={batches} />
            </div>
        </AppLayout>
    );
}
