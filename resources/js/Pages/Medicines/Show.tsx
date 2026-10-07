import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Can } from '@/Components/Can';
import ExpiryBadge from '@/Components/ExpiryBadge';
import StockLevelBadge from '@/Components/StockLevelBadge';
import type { Medicine, MedicineBatch, PageProps } from '@/types';

interface Props extends PageProps {
    medicine: Medicine & { batches?: MedicineBatch[] };
}

export default function Show({ medicine }: Props) {
    const batches = medicine.batches ?? [];
    const stockOnHand = batches.reduce((sum, b) => sum + b.quantity_on_hand, 0);

    const details: Array<{ label: string; value: string }> = [
        { label: 'SKU', value: medicine.sku },
        { label: 'Generic name', value: medicine.generic_name ?? '—' },
        { label: 'Category', value: medicine.category ?? '—' },
        { label: 'Strength', value: medicine.strength ?? '—' },
        { label: 'Unit type', value: medicine.unit_type },
        { label: 'Min stock level', value: String(medicine.min_stock_level) },
        { label: 'Reorder level', value: String(medicine.reorder_level) },
    ];

    return (
        <AppLayout>
            <Head title={medicine.name} />
            <div className="space-y-6">
                <div>
                    <Link href="/medicines" className="text-sm text-gray-500 hover:text-gray-700">← Medicine Catalog</Link>
                    <div className="mt-2 flex items-start justify-between gap-4">
                        <div>
                            <h1 className="flex items-center gap-3 text-2xl font-semibold text-gray-900">
                                {medicine.name}
                                <span className={medicine.is_active ? 'badge-active' : 'badge-suspended'}>
                                    {medicine.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </h1>
                            {medicine.generic_name && <p className="mt-1 text-sm text-gray-500">{medicine.generic_name}</p>}
                        </div>
                        <div className="flex items-center gap-2">
                            <Can permission="stock-movements.view">
                                <Link href={`/stock-movements?medicine_id=${medicine.id}`} className="btn-secondary">
                                    Stock movements
                                </Link>
                            </Can>
                            <Can role="tenant-admin">
                                <Link href={`/admin/medicines/${medicine.id}/edit`} className="btn-primary">Edit</Link>
                            </Can>
                        </div>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="card p-6 lg:col-span-2">
                        <h2 className="mb-4 font-medium text-gray-900">Details</h2>
                        <dl className="grid grid-cols-2 gap-4 sm:grid-cols-3">
                            {details.map((d) => (
                                <div key={d.label}>
                                    <dt className="text-xs uppercase tracking-wide text-gray-500">{d.label}</dt>
                                    <dd className="mt-1 text-sm capitalize text-gray-900">{d.value}</dd>
                                </div>
                            ))}
                        </dl>
                    </div>

                    <div className="card p-6">
                        <h2 className="mb-4 font-medium text-gray-900">Stock on hand</h2>
                        <p className="text-3xl font-semibold text-gray-900">{stockOnHand}</p>
                        <p className="mb-3 text-xs capitalize text-gray-500">{medicine.unit_type}s across {batches.length} active batch{batches.length === 1 ? '' : 'es'}</p>
                        <StockLevelBadge medicine={{ reorder_level: medicine.reorder_level, stock_on_hand: stockOnHand }} />
                    </div>
                </div>

                <div className="card overflow-hidden">
                    <div className="flex items-center justify-between border-b px-6 py-4">
                        <h2 className="font-medium text-gray-900">Active batches</h2>
                        <Can permission="medicine-batches.create">
                            <Link href="/medicine-batches/create" className="text-sm font-medium text-primary-600 hover:text-primary-700">
                                Receive stock
                            </Link>
                        </Can>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50 text-left text-xs text-gray-500">
                                <tr>
                                    <th className="px-4 py-2">Batch no.</th>
                                    <th className="px-4 py-2">Lot</th>
                                    <th className="px-4 py-2">Expiry</th>
                                    <th className="px-4 py-2">Received</th>
                                    <th className="px-4 py-2">On hand</th>
                                    <th className="px-4 py-2">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y bg-white">
                                {batches.map((b) => (
                                    <tr key={b.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-2 font-mono text-xs">{b.batch_number}</td>
                                        <td className="px-4 py-2 text-gray-500">{b.lot_number ?? '—'}</td>
                                        <td className="px-4 py-2"><ExpiryBadge expiryDate={b.expiry_date.slice(0, 10)} /></td>
                                        <td className="px-4 py-2 text-gray-500">{b.quantity_received}</td>
                                        <td className="px-4 py-2 font-medium">{b.quantity_on_hand}</td>
                                        <td className="px-4 py-2 capitalize">{b.status}</td>
                                    </tr>
                                ))}
                                {batches.length === 0 && (
                                    <tr><td colSpan={6} className="px-4 py-8 text-center text-gray-400">No active batches.</td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
