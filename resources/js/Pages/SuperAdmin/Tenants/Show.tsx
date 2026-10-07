import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import type { Tenant, User } from '@/types';

interface Props {
    tenant: Tenant & { users?: User[] };
}

const formatDate = (value: string | null | undefined): string =>
    value ? new Date(value).toLocaleDateString() : '—';

export default function TenantsShow({ tenant }: Props) {
    const users = tenant.users ?? [];
    const isSuspended = tenant.status === 'suspended';

    const toggleStatus = () => {
        const next = isSuspended ? 'active' : 'suspended';
        const verb = isSuspended ? 'Activate' : 'Suspend';
        if (!confirm(`${verb} "${tenant.name}"?`)) return;
        router.put(`/super-admin/tenants/${tenant.id}`, {
            name: tenant.name,
            status: next,
            plan: tenant.subscription_plan,
        });
    };

    const details: Array<{ label: string; value: string }> = [
        { label: 'Slug', value: tenant.slug },
        { label: 'Plan', value: tenant.subscription_plan },
        { label: 'Trial ends', value: formatDate(tenant.trial_ends_at) },
        { label: 'Created', value: formatDate(tenant.created_at) },
    ];

    return (
        <AppLayout>
            <Head title={tenant.name} />

            <div className="space-y-6">
                <div>
                    <Link href="/super-admin/tenants" className="text-sm text-gray-500 hover:text-gray-700">← Tenants</Link>
                    <div className="mt-2 flex flex-wrap items-center justify-between gap-4">
                        <h1 className="flex items-center gap-3 text-2xl font-semibold text-gray-900">
                            {tenant.name}
                            <span className={tenant.status === 'suspended' ? 'badge-suspended' : 'badge-active'}>
                                {tenant.status}
                            </span>
                        </h1>
                        <div className="flex items-center gap-2">
                            <Link href={`/super-admin/tenants/${tenant.id}/edit`} className="btn-secondary">Edit</Link>
                            <button
                                type="button"
                                onClick={toggleStatus}
                                className={isSuspended ? 'btn-primary' : 'btn-danger'}
                            >
                                {isSuspended ? 'Activate' : 'Suspend'}
                            </button>
                        </div>
                    </div>
                </div>

                <div className="card p-6">
                    <h2 className="mb-4 font-medium text-gray-900">Details</h2>
                    <dl className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        {details.map((d) => (
                            <div key={d.label}>
                                <dt className="text-xs uppercase tracking-wide text-gray-500">{d.label}</dt>
                                <dd className="mt-1 text-sm capitalize text-gray-900">{d.value}</dd>
                            </div>
                        ))}
                    </dl>
                </div>

                <div className="card overflow-hidden">
                    <div className="border-b px-6 py-4">
                        <h2 className="font-medium text-gray-900">Users ({users.length})</h2>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50 text-left text-xs text-gray-500">
                                <tr>
                                    <th className="px-4 py-2">Name</th>
                                    <th className="px-4 py-2">Email</th>
                                    <th className="px-4 py-2">Verified</th>
                                    <th className="px-4 py-2">2FA</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y bg-white">
                                {users.map((u) => (
                                    <tr key={u.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-2 font-medium text-gray-900">{u.name}</td>
                                        <td className="px-4 py-2 text-gray-600">{u.email}</td>
                                        <td className="px-4 py-2 text-gray-500">{u.email_verified_at ? 'Yes' : 'No'}</td>
                                        <td className="px-4 py-2 text-gray-500">{u.two_factor_confirmed_at ? 'Enabled' : 'Off'}</td>
                                    </tr>
                                ))}
                                {users.length === 0 && (
                                    <tr><td colSpan={4} className="px-4 py-8 text-center text-gray-400">No users.</td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
