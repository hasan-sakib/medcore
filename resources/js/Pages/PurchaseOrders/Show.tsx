import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { StatusBadge } from '@/Components/StatusBadge';
import type { PageProps, PurchaseOrder } from '@/types';

// Laravel serialises the `createdBy` relation as `created_by`, which replaces the FK id.
type OrderWithCreator = Omit<PurchaseOrder, 'created_by'> & {
    created_by: number | { id: number; name: string } | null;
};

interface Props extends PageProps {
    order: OrderWithCreator;
}

type Status = PurchaseOrder['status'];

const NEXT_ACTIONS: Record<Status, { status: Status; label: string; danger?: boolean }[]> = {
    draft: [
        { status: 'sent', label: 'Mark as Sent' },
        { status: 'cancelled', label: 'Cancel Order', danger: true },
    ],
    sent: [
        { status: 'received', label: 'Mark as Received' },
        { status: 'cancelled', label: 'Cancel Order', danger: true },
    ],
    received: [],
    cancelled: [{ status: 'draft', label: 'Reopen as Draft' }],
};

const fmtDate = (value: string | null): string => (value ? new Date(value).toLocaleDateString() : '—');

export default function Show({ order }: Props) {
    const items = order.items ?? [];
    const total = items.reduce((sum, item) => sum + item.quantity_ordered * Number(item.unit_price), 0);
    const creator = typeof order.created_by === 'object' && order.created_by ? order.created_by.name : '—';

    const changeStatus = (status: Status, label: string) => {
        if (confirm(`${label}?`)) {
            router.patch(`/admin/purchase-orders/${order.id}`, { status }, { preserveScroll: true });
        }
    };

    return (
        <AppLayout>
            <Head title={`PO ${order.po_number}`} />
            <div className="max-w-4xl space-y-6">
                <div>
                    <Link href="/admin/purchase-orders" className="text-sm text-gray-500 hover:text-gray-700">← Purchase Orders</Link>
                    <div className="mt-2 flex flex-wrap items-center justify-between gap-3">
                        <div className="flex items-center gap-3">
                            <h1 className="font-mono text-2xl font-semibold text-gray-900">{order.po_number}</h1>
                            <StatusBadge status={order.status} />
                        </div>
                        <div className="flex gap-2">
                            {NEXT_ACTIONS[order.status].map((action) => (
                                <button
                                    key={action.status}
                                    type="button"
                                    onClick={() => changeStatus(action.status, action.label)}
                                    className={action.danger ? 'btn-danger' : 'btn-primary'}
                                >
                                    {action.label}
                                </button>
                            ))}
                        </div>
                    </div>
                </div>

                <div className="card p-6">
                    <dl className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-3">
                        <div>
                            <dt className="text-gray-500">Supplier</dt>
                            <dd className="font-medium text-gray-900">{order.supplier?.name ?? '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-gray-500">Created by</dt>
                            <dd className="font-medium text-gray-900">{creator}</dd>
                        </div>
                        <div>
                            <dt className="text-gray-500">Created</dt>
                            <dd className="font-medium text-gray-900">{fmtDate(order.created_at)}</dd>
                        </div>
                        <div>
                            <dt className="text-gray-500">Ordered</dt>
                            <dd className="font-medium text-gray-900">{fmtDate(order.ordered_at)}</dd>
                        </div>
                        <div>
                            <dt className="text-gray-500">Expected delivery</dt>
                            <dd className="font-medium text-gray-900">{fmtDate(order.expected_delivery_date)}</dd>
                        </div>
                        <div>
                            <dt className="text-gray-500">Received</dt>
                            <dd className="font-medium text-gray-900">{fmtDate(order.received_at)}</dd>
                        </div>
                    </dl>
                    {order.notes && (
                        <div className="mt-4 border-t pt-4 text-sm">
                            <p className="text-gray-500">Notes</p>
                            <p className="whitespace-pre-line text-gray-900">{order.notes}</p>
                        </div>
                    )}
                </div>

                <div className="card overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left text-xs text-gray-500">
                            <tr>
                                <th className="px-4 py-2">Medicine</th>
                                <th className="px-4 py-2">SKU</th>
                                <th className="px-4 py-2 text-right">Ordered</th>
                                <th className="px-4 py-2 text-right">Received</th>
                                <th className="px-4 py-2 text-right">Unit Price</th>
                                <th className="px-4 py-2 text-right">Line Total</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {items.map((item) => (
                                <tr key={item.id}>
                                    <td className="px-4 py-2 font-medium">{item.medicine?.name ?? `#${item.medicine_id}`}</td>
                                    <td className="px-4 py-2 font-mono text-xs">{item.medicine?.sku ?? '—'}</td>
                                    <td className="px-4 py-2 text-right">{item.quantity_ordered}</td>
                                    <td className="px-4 py-2 text-right">{item.quantity_received}</td>
                                    <td className="px-4 py-2 text-right">{Number(item.unit_price).toFixed(2)}</td>
                                    <td className="px-4 py-2 text-right">{(item.quantity_ordered * Number(item.unit_price)).toFixed(2)}</td>
                                </tr>
                            ))}
                            {items.length === 0 && (
                                <tr><td colSpan={6} className="px-4 py-8 text-center text-gray-400">No items.</td></tr>
                            )}
                        </tbody>
                        <tfoot>
                            <tr className="border-t bg-gray-50">
                                <td colSpan={5} className="px-4 py-2 text-right font-medium">Total</td>
                                <td className="px-4 py-2 text-right font-semibold">{total.toFixed(2)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
