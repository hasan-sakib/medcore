import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Pagination } from '@/Components/Pagination';
import type { PageProps, PaginatedResource, ChargeItem } from '@/types';

type ChargeItemRow = ChargeItem & { is_used: boolean };

interface Props extends PageProps {
    items: PaginatedResource<ChargeItemRow>;
    filters: { search?: string; category?: string; status?: string };
    categories: string[];
}

const money = (v: number | string) =>
    `$${Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export default function ChargeItemsIndex({ items, filters, categories }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [category, setCategory] = useState(filters.category ?? '');
    const [status, setStatus] = useState(filters.status ?? '');

    const applyFilters = () => {
        router.get('/billing/charge-items', { search, category, status }, { preserveScroll: true, replace: true });
    };

    const remove = (item: ChargeItemRow) => {
        const msg = item.is_used
            ? `"${item.name}" is used on invoices, so it will be deactivated rather than deleted. Continue?`
            : `Delete "${item.name}"? This cannot be undone.`;
        if (confirm(msg)) {
            router.delete(`/billing/charge-items/${item.id}`, { preserveScroll: true });
        }
    };

    return (
        <AppLayout>
            <Head title="Charge Items" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-gray-900">Charge Items</h1>
                        <p className="text-sm text-gray-500">Price list used when adding invoice lines.</p>
                    </div>
                    <Link href="/billing/charge-items/create" className="btn-primary text-sm">+ New Charge Item</Link>
                </div>

                <div className="flex flex-wrap gap-3">
                    <input type="text" className="form-input max-w-xs" placeholder="Search name or code…"
                        value={search} onChange={e => setSearch(e.target.value)}
                        onKeyDown={e => e.key === 'Enter' && applyFilters()} />
                    <select className="form-input w-44 capitalize" value={category} onChange={e => setCategory(e.target.value)}>
                        <option value="">All categories</option>
                        {categories.map(c => <option key={c} value={c}>{c}</option>)}
                    </select>
                    <select className="form-input w-36" value={status} onChange={e => setStatus(e.target.value)}>
                        <option value="">Any status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <button onClick={applyFilters} className="btn-secondary">Filter</button>
                </div>

                <div className="card overflow-hidden">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                {['Code', 'Name', 'Category', 'Unit price', 'Tax', 'Status', ''].map(h => (
                                    <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">{h}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {items.data.map(item => (
                                <tr key={item.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3 font-mono text-xs font-semibold text-primary-700">{item.code}</td>
                                    <td className="px-4 py-3 text-sm font-medium text-gray-900">{item.name}</td>
                                    <td className="px-4 py-3 text-sm text-gray-600 capitalize">{item.category}</td>
                                    <td className="px-4 py-3 text-sm">{money(item.unit_price)}</td>
                                    <td className="px-4 py-3 text-sm text-gray-600">{Number(item.tax_rate)}%</td>
                                    <td className="px-4 py-3">
                                        <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${item.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}`}>
                                            {item.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-right space-x-3 whitespace-nowrap">
                                        <Link href={`/billing/charge-items/${item.id}/edit`} className="text-xs text-primary-600 hover:underline">Edit</Link>
                                        {(item.is_active || !item.is_used) && (
                                            <button onClick={() => remove(item)} className="text-xs text-danger-600 hover:underline">
                                                {item.is_used ? 'Deactivate' : 'Delete'}
                                            </button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {items.data.length === 0 && (
                        <p className="text-center py-10 text-sm text-gray-400">No charge items found.</p>
                    )}
                </div>

                <Pagination data={items} />
            </div>
        </AppLayout>
    );
}
