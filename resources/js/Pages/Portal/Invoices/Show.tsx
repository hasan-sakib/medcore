import { Head } from '@inertiajs/react';
import PortalLayout from '@/Layouts/PortalLayout';
import type { PageProps, Invoice, InvoiceLine, Payment } from '@/types';

type FullInvoice = Invoice & { lines: InvoiceLine[]; payments: Payment[] };

interface Props extends PageProps {
    invoice: FullInvoice;
}

export default function PortalInvoiceShow({ invoice }: Props) {
    return (
        <PortalLayout>
            <Head title={`Invoice ${invoice.invoice_number}`} />

            <div className="space-y-5 max-w-2xl">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-gray-900 font-mono">{invoice.invoice_number}</h1>
                        <p className="text-sm text-gray-500">{new Date(invoice.created_at).toLocaleDateString()}</p>
                    </div>
                    <a
                        href={`/portal/invoices/${invoice.id}/pdf`}
                        target="_blank"
                        className="btn-secondary text-xs"
                    >
                        Download PDF
                    </a>
                </div>

                {/* Totals */}
                <div className="grid grid-cols-3 gap-3">
                    <div className="bg-white rounded-xl border border-gray-200 p-3">
                        <p className="text-xs text-gray-500">Total</p>
                        <p className="text-lg font-bold text-gray-900">${Number(invoice.total_amount).toFixed(2)}</p>
                    </div>
                    <div className="bg-white rounded-xl border border-gray-200 p-3">
                        <p className="text-xs text-gray-500">Paid</p>
                        <p className="text-lg font-bold text-green-600">${Number(invoice.amount_paid).toFixed(2)}</p>
                    </div>
                    <div className="bg-white rounded-xl border border-gray-200 p-3">
                        <p className="text-xs text-gray-500">Balance Due</p>
                        <p className="text-lg font-bold text-red-600">${Number(invoice.amount_due).toFixed(2)}</p>
                    </div>
                </div>

                {/* Line items */}
                <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div className="px-4 py-3 border-b border-gray-100">
                        <h2 className="font-semibold text-gray-900 text-sm">Items</h2>
                    </div>
                    <table className="min-w-full divide-y divide-gray-100 text-sm">
                        <thead className="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                            <tr>
                                <th className="px-4 py-2 text-left">Description</th>
                                <th className="px-4 py-2 text-right">Qty</th>
                                <th className="px-4 py-2 text-right">Unit Price</th>
                                <th className="px-4 py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-50">
                            {invoice.lines?.map(line => (
                                <tr key={line.id}>
                                    <td className="px-4 py-2">{line.description}</td>
                                    <td className="px-4 py-2 text-right">{Number(line.quantity)}</td>
                                    <td className="px-4 py-2 text-right">${Number(line.unit_price).toFixed(2)}</td>
                                    <td className="px-4 py-2 text-right font-semibold">${Number(line.line_total).toFixed(2)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* Payment history */}
                {(invoice.payments?.length ?? 0) > 0 && (
                    <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div className="px-4 py-3 border-b border-gray-100">
                            <h2 className="font-semibold text-gray-900 text-sm">Payment History</h2>
                        </div>
                        <div className="divide-y divide-gray-50 text-sm">
                            {invoice.payments?.map(p => (
                                <div key={p.id} className="px-4 py-3 flex justify-between">
                                    <div>
                                        <p className="capitalize font-medium">{p.payment_method.replace('_', ' ')}</p>
                                        <p className="text-xs text-gray-400">{new Date(p.paid_at).toLocaleString()}</p>
                                    </div>
                                    <p className="font-semibold text-green-700">${Number(p.amount).toFixed(2)}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </PortalLayout>
    );
}
