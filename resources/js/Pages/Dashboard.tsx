import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';

export default function Dashboard() {
    const { auth, tenant, roles } = usePage<PageProps>().props;

    const isSuperAdmin = roles.includes('super-admin');

    return (
        <AppLayout>
            <Head title="Dashboard" />

            <div className="space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-semibold text-gray-900">
                        {isSuperAdmin ? 'Platform Dashboard' : `Welcome back, ${auth.user?.name}`}
                    </h1>
                    {tenant && (
                        <p className="text-sm text-gray-500 mt-1">{tenant.name}</p>
                    )}
                </div>

                {/* Super Admin — platform overview */}
                {isSuperAdmin && (
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <StatCard label="Active Tenants" value="—" icon="🏥" />
                        <StatCard label="Total Users" value="—" icon="👥" />
                        <StatCard label="Platform Status" value="Healthy" icon="✅" />
                    </div>
                )}

                {/* Tenant dashboard */}
                {!isSuperAdmin && (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <StatCard label="Patients Today" value="—" icon="🧑‍⚕️" />
                        <StatCard label="Active Beds" value="—" icon="🛏️" />
                        <StatCard label="Appointments" value="—" icon="📅" />
                        <StatCard label="Pending Invoices" value="—" icon="💳" />
                    </div>
                )}

                {/* Placeholder content */}
                <div className="card p-6">
                    <p className="text-sm text-gray-500 text-center py-8">
                        Module panels will appear here as phases are implemented.
                    </p>
                </div>
            </div>
        </AppLayout>
    );
}

function StatCard({ label, value, icon }: { label: string; value: string; icon: string }) {
    return (
        <div className="card p-5 flex items-center gap-4">
            <span className="text-3xl">{icon}</span>
            <div>
                <p className="text-2xl font-semibold text-gray-900">{value}</p>
                <p className="text-sm text-gray-500">{label}</p>
            </div>
        </div>
    );
}
