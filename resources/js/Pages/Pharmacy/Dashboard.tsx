import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import ExpiryBadge from '@/Components/ExpiryBadge';
import StockLevelBadge from '@/Components/StockLevelBadge';
import type { MedicineBatch, Medicine, PageProps } from '@/types';

interface Stats {
    total_medicines: number;
    expiring_soon_count: number;
    low_stock_count: number;
}

interface Props extends PageProps {
    stats: Stats;
    expiryAlerts: MedicineBatch[];
    lowStockAlerts: (Medicine & { stock_on_hand: number })[];
}

export default function Dashboard({ stats, expiryAlerts, lowStockAlerts }: Props) {
    return (
        <AppLayout>
            <Head title="Pharmacy Dashboard" />
            <div className="space-y-6">
                <h1 className="text-xl font-semibold text-gray-900">Pharmacy Dashboard</h1>

                {/* Stats cards */}
                <div className="grid grid-cols-3 gap-4">
                    <div className="rounded-lg border bg-white p-4">
                        <p className="text-sm text-gray-500">Total Medicines</p>
                        <p className="mt-1 text-2xl font-bold text-gray-900">{stats.total_medicines}</p>
                    </div>
                    <div className="rounded-lg border bg-white p-4">
                        <p className="text-sm text-gray-500">Expiring in 30 Days</p>
                        <p className={`mt-1 text-2xl font-bold ${stats.expiring_soon_count > 0 ? 'text-red-600' : 'text-gray-900'}`}>
                            {stats.expiring_soon_count}
                        </p>
                    </div>
                    <div className="rounded-lg border bg-white p-4">
                        <p className="text-sm text-gray-500">Low Stock Alerts</p>
                        <p className={`mt-1 text-2xl font-bold ${stats.low_stock_count > 0 ? 'text-amber-600' : 'text-gray-900'}`}>
                            {stats.low_stock_count}
                        </p>
                    </div>
                </div>

                {/* Expiry alerts */}
                {expiryAlerts.length > 0 && (
                    <div className="rounded-lg border bg-white">
                        <div className="border-b px-4 py-3">
                            <h2 className="text-sm font-semibold text-gray-700">Expiring Soon (30 days)</h2>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-gray-50 text-left text-xs text-gray-500">
                                    <tr>
                                        <th className="px-4 py-2">Medicine</th>
                                        <th className="px-4 py-2">Batch</th>
                                        <th className="px-4 py-2">Expiry</th>
                                        <th className="px-4 py-2">On Hand</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {expiryAlerts.map((batch) => (
                                        <tr key={batch.id}>
                                            <td className="px-4 py-2">{batch.medicine?.name}</td>
                                            <td className="px-4 py-2 font-mono text-xs">{batch.batch_number}</td>
                                            <td className="px-4 py-2"><ExpiryBadge expiryDate={batch.expiry_date} /></td>
                                            <td className="px-4 py-2">{batch.quantity_on_hand}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {/* Low stock alerts */}
                {lowStockAlerts.length > 0 && (
                    <div className="rounded-lg border bg-white">
                        <div className="border-b px-4 py-3">
                            <h2 className="text-sm font-semibold text-gray-700">Low Stock</h2>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-gray-50 text-left text-xs text-gray-500">
                                    <tr>
                                        <th className="px-4 py-2">Medicine</th>
                                        <th className="px-4 py-2">SKU</th>
                                        <th className="px-4 py-2">Stock</th>
                                        <th className="px-4 py-2">Reorder Level</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {lowStockAlerts.map((med) => (
                                        <tr key={med.id}>
                                            <td className="px-4 py-2">{med.name}</td>
                                            <td className="px-4 py-2 font-mono text-xs">{med.sku}</td>
                                            <td className="px-4 py-2"><StockLevelBadge medicine={med} /></td>
                                            <td className="px-4 py-2 text-gray-500">{med.reorder_level}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {expiryAlerts.length === 0 && lowStockAlerts.length === 0 && (
                    <p className="text-sm text-gray-500">No alerts. Stock levels are healthy.</p>
                )}
            </div>
        </AppLayout>
    );
}
