import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Pagination } from '@/Components/Pagination';
import type { PageProps, PaginatedResource, TaxConfig } from '@/types';

interface Props extends PageProps {
    configs: PaginatedResource<TaxConfig>;
    filters: { search?: string; applies_to?: string; status?: string };
    appliesTo: string[];
}

export default function TaxConfigsIndex({ configs, filters, appliesTo }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [target, setTarget] = useState(filters.applies_to ?? '');
    const [status, setStatus] = useState(filters.status ?? '');

    const applyFilters = () => {
        router.get('/billing/tax-configs', { search, applies_to: target, status }, { preserveScroll: true, replace: true });
    };

    const deactivate = (cfg: TaxConfig) => {
        if (confirm(`Deactivate "${cfg.name}"? Existing invoices keep the rate they were created with.`)) {
            router.delete(`/billing/tax-configs/${cfg.id}`, { preserveScroll: true });
        }
    };

    return (
        <AppLayout>
            <Head title="Tax Configuration" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-gray-900">Tax Configuration</h1>
                        <p className="text-sm text-gray-500">Tax rates by charge category.</p>
                    </div>
                    <Link href="/billing/tax-configs/create" className="btn-primary text-sm">+ New Tax Rate</Link>
                </div>

                <div className="flex flex-wrap gap-3">
                    <input type="text" className="form-input max-w-xs" placeholder="Search name…"
                        value={search} onChange={e => setSearch(e.target.value)}
                        onKeyDown={e => e.key === 'Enter' && applyFilters()} />
                    <select className="form-input w-44 capitalize" value={target} onChange={e => setTarget(e.target.value)}>
                        <option value="">Applies to: any</option>
                        {appliesTo.map(a => <option key={a} value={a}>{a}</option>)}
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
                                {['Name', 'Rate', 'Applies to', 'Status', ''].map(h => (
                                    <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">{h}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {configs.data.map(cfg => (
                                <tr key={cfg.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3 text-sm font-medium text-gray-900">{cfg.name}</td>
                                    <td className="px-4 py-3 text-sm">{Number(cfg.rate)}%</td>
                                    <td className="px-4 py-3 text-sm text-gray-600 capitalize">{cfg.applies_to}</td>
                                    <td className="px-4 py-3">
                                        <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${cfg.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}`}>
                                            {cfg.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-right space-x-3 whitespace-nowrap">
                                        <Link href={`/billing/tax-configs/${cfg.id}/edit`} className="text-xs text-primary-600 hover:underline">Edit</Link>
                                        {cfg.is_active && (
                                            <button onClick={() => deactivate(cfg)} className="text-xs text-danger-600 hover:underline">Deactivate</button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {configs.data.length === 0 && (
                        <p className="text-center py-10 text-sm text-gray-400">No tax configurations found.</p>
                    )}
                </div>

                <Pagination data={configs} />
            </div>
        </AppLayout>
    );
}
