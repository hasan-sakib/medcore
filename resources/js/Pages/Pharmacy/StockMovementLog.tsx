import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Pagination } from '@/Components/Pagination';
import type { Medicine, PageProps, PaginatedResource, StockMovement } from '@/types';

interface Filters {
    medicine_id?: string;
    movement_type?: string;
    from?: string;
    to?: string;
}

interface Props extends PageProps {
    movements: PaginatedResource<StockMovement>;
    medicines: Medicine[];
    filters: Filters;
}

const TYPE_COLORS: Record<string, string> = {
    in: 'bg-green-100 text-green-700',
    out: 'bg-red-100 text-red-700',
    adjustment: 'bg-blue-100 text-blue-700',
    return: 'bg-purple-100 text-purple-700',
    waste: 'bg-gray-100 text-gray-600',
};

export default function StockMovementLog({ movements, medicines, filters }: Props) {
    const handleFilter = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const fd = new FormData(e.currentTarget);
        router.get('/stock-movements', Object.fromEntries(fd), { preserveState: true, replace: true });
    };

    return (
        <AppLayout>
            <Head title="Stock Movement Log" />
            <div className="space-y-4">
                <h1 className="text-xl font-semibold text-gray-900">Stock Movement Log</h1>

                <form onSubmit={handleFilter} className="flex flex-wrap gap-2">
                    <select name="medicine_id" defaultValue={filters.medicine_id ?? ''} className="rounded border border-gray-300 px-2 py-1.5 text-sm">
                        <option value="">All medicines</option>
                        {medicines.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
                    </select>
                    <select name="movement_type" defaultValue={filters.movement_type ?? ''} className="rounded border border-gray-300 px-2 py-1.5 text-sm">
                        <option value="">All types</option>
                        {['in', 'out', 'adjustment', 'return', 'waste'].map((t) => (
                            <option key={t} value={t}>{t}</option>
                        ))}
                    </select>
                    <input type="date" name="from" defaultValue={filters.from ?? ''} className="rounded border border-gray-300 px-2 py-1.5 text-sm" />
                    <input type="date" name="to" defaultValue={filters.to ?? ''} className="rounded border border-gray-300 px-2 py-1.5 text-sm" />
                    <button type="submit" className="rounded bg-gray-100 px-3 py-1.5 text-sm hover:bg-gray-200">Filter</button>
                </form>

                <div className="overflow-x-auto rounded-lg border bg-white">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left text-xs text-gray-500">
                            <tr>
                                <th className="px-4 py-2">Date</th>
                                <th className="px-4 py-2">Medicine</th>
                                <th className="px-4 py-2">Batch</th>
                                <th className="px-4 py-2">Type</th>
                                <th className="px-4 py-2">Qty</th>
                                <th className="px-4 py-2">By</th>
                                <th className="px-4 py-2">Notes</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {movements.data.map((mv) => (
                                <tr key={mv.id}>
                                    <td className="px-4 py-2 text-xs text-gray-500">{mv.created_at.slice(0, 16).replace('T', ' ')}</td>
                                    <td className="px-4 py-2">{mv.medicine?.name}</td>
                                    <td className="px-4 py-2 font-mono text-xs">{mv.batch?.batch_number}</td>
                                    <td className="px-4 py-2">
                                        <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${TYPE_COLORS[mv.movement_type] ?? ''}`}>
                                            {mv.movement_type}
                                        </span>
                                    </td>
                                    <td className={`px-4 py-2 font-medium ${mv.quantity > 0 ? 'text-green-600' : 'text-red-600'}`}>
                                        {mv.quantity > 0 ? '+' : ''}{mv.quantity}
                                    </td>
                                    <td className="px-4 py-2 text-gray-500">{mv.createdBy?.name}</td>
                                    <td className="px-4 py-2 text-gray-400 text-xs">{mv.notes ?? '—'}</td>
                                </tr>
                            ))}
                            {movements.data.length === 0 && (
                                <tr><td colSpan={7} className="px-4 py-8 text-center text-gray-400">No movements found.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination data={movements} />
            </div>
        </AppLayout>
    );
}
