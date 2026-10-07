import { Head, Link, router } from '@inertiajs/react';
import PortalLayout from '@/Layouts/PortalLayout';
import type { PageProps, PaginatedResource, Invoice } from '@/types';

interface Props extends PageProps {
    invoices: PaginatedResource<Invoice>;
    summary: { total_paid: number; total_due: number };
}

const STATUS_CHIP: Record<string, string> = {
    draft:          'bg-gray-100 text-gray-600',
    sent:           'bg-blue-100 text-blue-700',
    paid:           'bg-green-100 text-green-700',
    partially_paid: 'bg-yellow-100 text-yellow-700',
    cancelled:      'bg-red-100 text-red-600',
};

export default function PortalInvoicesIndex({ invoices, summary }: Props) {
    return (
        <PortalLayout>
            <Head title="My Invoices" />

            <div className="space-y-5">
                <h1 className="text-xl font-semibold text-gray-900">My Invoices</h1>

                <div className="grid grid-cols-2 gap-4">
                    <div className="bg-white rounded-xl border border-gray-200 p-4">
                        <p className="text-xs text-gray-500">Outstanding Balance</p>
                        <p className="text-2xl font-bold text-red-600 mt-1">${Number(summary.total_due).toFixed(2)}</p>
                    </div>
                    <div className="bg-white rounded-xl border border-gray-200 p-4">
                        <p className="text-xs text-gray-500">Total Paid</p>
                        <p className="text-2xl font-bold text-green-600 mt-1">${Number(summary.total_paid).toFixed(2)}</p>
                    </div>
                </div>

                <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                {['Invoice #', 'Date', 'Total', 'Paid', 'Due', 'Status', ''].map(h => (
                                    <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">{h}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100 text-sm">
                            {invoices.data.map(inv => (
                                <tr key={inv.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3 font-mono text-xs font-semibold text-primary-700">{inv.invoice_number}</td>
                                    <td className="px-4 py-3 text-gray-500">{new Date(inv.created_at).toLocaleDateString()}</td>
                                    <td className="px-4 py-3 font-medium">${Number(inv.total_amount).toFixed(2)}</td>
                                    <td className="px-4 py-3 text-green-600">${Number(inv.amount_paid).toFixed(2)}</td>
                                    <td className="px-4 py-3 text-red-600 font-medium">${Number(inv.amount_due).toFixed(2)}</td>
                                    <td className="px-4 py-3">
                                        <span className={`text-xs px-2 py-0.5 rounded-full capitalize font-medium ${STATUS_CHIP[inv.status] ?? ''}`}>
                                            {inv.status.replace('_', ' ')}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 flex items-center gap-2">
                                        <Link href={`/portal/invoices/${inv.id}`} className="text-xs text-primary-600 hover:underline">
                                            View
                                        </Link>
                                        <a href={`/portal/invoices/${inv.id}/pdf`} className="text-xs text-gray-500 hover:underline" target="_blank">
                                            PDF
                                        </a>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {invoices.data.length === 0 && (
                        <p className="text-center py-10 text-sm text-gray-400">No invoices found.</p>
                    )}
                </div>

                {invoices.last_page > 1 && (
                    <div className="flex justify-center gap-1">
                        {invoices.links.map((link, i) => (
                            <button
                                key={i}
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url)}
                                className={`px-3 py-1.5 text-xs rounded ${link.active ? 'bg-emerald-600 text-white' : 'bg-white border border-gray-300 text-gray-600 disabled:opacity-40'}`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </PortalLayout>
    );
}
