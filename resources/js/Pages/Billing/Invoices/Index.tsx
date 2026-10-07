import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps, PaginatedResource, Invoice, Patient } from '@/types';

type InvoiceRow = Invoice & { patient: Pick<Patient, 'id' | 'first_name' | 'last_name' | 'mrn'> };

interface Props extends PageProps {
    invoices: PaginatedResource<InvoiceRow>;
    filters: { status?: string; search?: string };
    summary: { total_due: number; total_paid: number; draft_count: number };
}

const STATUS_CHIP: Record<string, string> = {
    draft:           'bg-gray-100 text-gray-700',
    sent:            'bg-blue-100 text-blue-700',
    paid:            'bg-green-100 text-green-700',
    partially_paid:  'bg-yellow-100 text-yellow-700',
    cancelled:       'bg-red-100 text-red-700',
    void:            'bg-gray-100 text-gray-400 line-through',
};

export default function InvoicesIndex({ invoices, filters, summary }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');

    const applyFilters = () => {
        router.get('/invoices', { search, status }, { preserveScroll: true, replace: true });
    };

    return (
        <AppLayout>
            <Head title="Invoices" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold text-gray-900">Invoices</h1>
                    <Link href="/invoices/create" className="btn-primary text-sm">+ New Invoice</Link>
                </div>

                {/* Summary cards */}
                <div className="grid grid-cols-3 gap-4">
                    {[
                        { label: 'Outstanding', value: summary.total_due, color: 'text-red-600' },
                        { label: 'Collected (this month)', value: summary.total_paid, color: 'text-green-600' },
                        { label: 'Drafts', value: summary.draft_count, color: 'text-gray-600', isCount: true },
                    ].map(({ label, value, color, isCount }) => (
                        <div key={label} className="bg-white rounded-xl border border-gray-200 p-4">
                            <p className="text-xs text-gray-500 mb-1">{label}</p>
                            <p className={`text-2xl font-bold ${color}`}>
                                {isCount ? value : `$${Number(value).toLocaleString('en-US', { minimumFractionDigits: 2 })}`}
                            </p>
                        </div>
                    ))}
                </div>

                {/* Filters */}
                <div className="flex gap-3">
                    <input
                        type="text"
                        className="form-input max-w-xs"
                        placeholder="Search invoice # or MRN…"
                        value={search}
                        onChange={e => setSearch(e.target.value)}
                        onKeyDown={e => e.key === 'Enter' && applyFilters()}
                    />
                    <select className="form-input w-44" value={status} onChange={e => { setStatus(e.target.value); }}>
                        <option value="">All statuses</option>
                        {['draft','sent','paid','partially_paid','cancelled'].map(s => (
                            <option key={s} value={s}>{s.replace('_', ' ')}</option>
                        ))}
                    </select>
                    <button onClick={applyFilters} className="btn-secondary">Filter</button>
                </div>

                {/* Table */}
                <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                {['Invoice #', 'Patient', 'Date', 'Total', 'Paid', 'Due', 'Status', ''].map(h => (
                                    <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">{h}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {invoices.data.map(inv => (
                                <tr key={inv.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3 font-mono text-xs font-semibold text-primary-700">{inv.invoice_number}</td>
                                    <td className="px-4 py-3">
                                        <div className="text-sm font-medium">{inv.patient.first_name} {inv.patient.last_name}</div>
                                        <div className="text-xs text-gray-400">{inv.patient.mrn}</div>
                                    </td>
                                    <td className="px-4 py-3 text-sm text-gray-500">{new Date(inv.created_at).toLocaleDateString()}</td>
                                    <td className="px-4 py-3 text-sm font-medium">${Number(inv.total_amount).toFixed(2)}</td>
                                    <td className="px-4 py-3 text-sm text-green-600">${Number(inv.amount_paid).toFixed(2)}</td>
                                    <td className="px-4 py-3 text-sm text-red-600 font-medium">${Number(inv.amount_due).toFixed(2)}</td>
                                    <td className="px-4 py-3">
                                        <span className={`text-xs px-2 py-0.5 rounded-full capitalize font-medium ${STATUS_CHIP[inv.status] ?? ''}`}>
                                            {inv.status.replace('_', ' ')}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3">
                                        <Link href={`/invoices/${inv.id}`} className="text-xs text-primary-600 hover:underline">View</Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {invoices.data.length === 0 && (
                        <p className="text-center py-10 text-sm text-gray-400">No invoices found.</p>
                    )}
                </div>

                {/* Pagination */}
                {invoices.last_page > 1 && (
                    <div className="flex justify-center gap-1">
                        {invoices.links.map((link, i) => (
                            <button
                                key={i}
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url)}
                                className={`px-3 py-1.5 text-xs rounded ${link.active ? 'bg-primary-600 text-white' : 'bg-white border border-gray-300 text-gray-600 disabled:opacity-40'}`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
