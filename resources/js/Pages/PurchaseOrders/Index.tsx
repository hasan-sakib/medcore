import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Pagination } from '@/Components/Pagination';
import { StatusBadge } from '@/Components/StatusBadge';
import type { PageProps, PaginatedResource, PurchaseOrder } from '@/types';

interface Props extends PageProps {
    orders: PaginatedResource<PurchaseOrder>;
    filters: { status?: string };
}

const STATUSES = ['draft', 'sent', 'received', 'cancelled'];

export default function Index({ orders, filters }: Props) {
    const handleFilter = (status: string) => {
        router.get('/admin/purchase-orders', status ? { status } : {}, { preserveState: true, replace: true });
    };

    return (
        <AppLayout>
            <Head title="Purchase Orders" />
            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold text-gray-900">Purchase Orders</h1>
                    <Link
                        href="/admin/purchase-orders/create"
                        className="rounded bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700"
                    >
                        New Purchase Order
                    </Link>
                </div>

                <div className="flex items-center gap-2">
                    <label htmlFor="status" className="text-sm text-gray-600">Status</label>
                    <select
                        id="status"
                        value={filters.status ?? ''}
                        onChange={(e) => handleFilter(e.target.value)}
                        className="rounded border border-gray-300 px-3 py-1.5 text-sm focus:border-primary-500 focus:outline-none"
                    >
                        <option value="">All</option>
                        {STATUSES.map((s) => (
                            <option key={s} value={s} className="capitalize">{s}</option>
                        ))}
                    </select>
                </div>

                <div className="card overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left text-xs text-gray-500">
                            <tr>
                                <th className="px-4 py-2">PO Number</th>
                                <th className="px-4 py-2">Supplier</th>
                                <th className="px-4 py-2">Items</th>
                                <th className="px-4 py-2">Status</th>
                                <th className="px-4 py-2">Expected Delivery</th>
                                <th className="px-4 py-2">Created</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {orders.data.map((order) => (
                                <tr key={order.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-2 font-mono text-xs">
                                        <Link href={`/admin/purchase-orders/${order.id}`} className="text-primary-600 hover:underline">
                                            {order.po_number}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-2">{order.supplier?.name ?? '—'}</td>
                                    <td className="px-4 py-2">{order.items_count ?? 0}</td>
                                    <td className="px-4 py-2"><StatusBadge status={order.status} /></td>
                                    <td className="px-4 py-2">
                                        {order.expected_delivery_date ? new Date(order.expected_delivery_date).toLocaleDateString() : '—'}
                                    </td>
                                    <td className="px-4 py-2 text-gray-500">{new Date(order.created_at).toLocaleDateString()}</td>
                                </tr>
                            ))}
                            {orders.data.length === 0 && (
                                <tr><td colSpan={6} className="px-4 py-8 text-center text-gray-400">No purchase orders found.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination data={orders} />
            </div>
        </AppLayout>
    );
}
