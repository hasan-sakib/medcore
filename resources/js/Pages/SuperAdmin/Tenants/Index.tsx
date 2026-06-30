import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import type { PaginatedResource, Tenant } from '@/types';

interface Props {
    tenants: PaginatedResource<Tenant>;
}

export default function TenantsIndex({ tenants }: Props) {
    const handleSuspend = (tenant: Tenant) => {
        if (confirm(`Suspend "${tenant.name}"?`)) {
            router.patch(`/super-admin/tenants/${tenant.id}`, {
                status: 'suspended',
                name: tenant.name,
            });
        }
    };

    return (
        <AppLayout>
            <Head title="Tenants" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-gray-900">Tenants</h1>
                        <p className="text-sm text-gray-500 mt-1">{tenants.total} total hospitals / clinics</p>
                    </div>
                    <Link href="/super-admin/tenants/create" className="btn-primary">
                        + New Tenant
                    </Link>
                </div>

                {/* Table */}
                <div className="card overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    {['Name', 'Slug', 'Plan', 'Status', 'Created', 'Actions'].map((h) => (
                                        <th key={h} className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {h}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {tenants.data.map((tenant) => (
                                    <tr key={tenant.id} className="hover:bg-gray-50 transition-colors">
                                        <td className="px-4 py-3">
                                            <p className="text-sm font-medium text-gray-900">{tenant.name}</p>
                                        </td>
                                        <td className="px-4 py-3">
                                            <code className="text-xs bg-gray-100 px-2 py-0.5 rounded">{tenant.slug}</code>
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-600 capitalize">{tenant.subscription_plan}</td>
                                        <td className="px-4 py-3">
                                            <span className={tenant.status === 'active' ? 'badge-active' : 'badge-suspended'}>
                                                {tenant.status}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-500">
                                            {new Date(tenant.created_at).toLocaleDateString()}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-3">
                                                <Link
                                                    href={`/super-admin/tenants/${tenant.id}`}
                                                    className="text-xs text-primary-600 hover:text-primary-700 font-medium"
                                                >
                                                    View
                                                </Link>
                                                {tenant.status === 'active' && (
                                                    <button
                                                        onClick={() => handleSuspend(tenant)}
                                                        className="text-xs text-danger-600 hover:text-danger-700 font-medium"
                                                    >
                                                        Suspend
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {tenants.data.length === 0 && (
                        <div className="text-center py-12">
                            <p className="text-gray-500 text-sm">No tenants provisioned yet.</p>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
