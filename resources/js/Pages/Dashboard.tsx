import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Can } from '@/Components/Can';
import type { PageProps } from '@/types';

interface Stats {
    patients_today: number;
    appointments_today: number;
    active_encounters: number;
    pending_prescriptions: number;
}

interface Props extends PageProps {
    stats?: Stats | null;
}

export default function Dashboard({ stats }: Props) {
    const { auth, tenant, roles } = usePage<PageProps>().props;
    const isSuperAdmin = (roles as string[]).includes('super-admin');

    return (
        <AppLayout>
            <Head title="Dashboard" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold text-gray-900">
                        {isSuperAdmin ? 'Platform Dashboard' : `Welcome back, ${auth.user?.name}`}
                    </h1>
                    {tenant && (
                        <p className="text-sm text-gray-500 mt-1">{tenant.name}</p>
                    )}
                </div>

                {isSuperAdmin && (
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <StatCard label="Platform Status" value="Healthy" icon="✅" />
                    </div>
                )}

                {!isSuperAdmin && stats && (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <StatCard label="Patients Today"        value={stats.patients_today}        icon="🧑‍⚕️" />
                        <StatCard label="Appointments Today"    value={stats.appointments_today}    icon="📅" />
                        <StatCard label="Active Encounters"     value={stats.active_encounters}     icon="🛏️" />
                        <StatCard label="Pending Prescriptions" value={stats.pending_prescriptions} icon="💊" />
                    </div>
                )}

                {!isSuperAdmin && (
                    <div className="rounded-lg border bg-white p-6">
                        <h2 className="text-sm font-semibold text-gray-700 mb-4">Quick Access</h2>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            <Can permission="patients.view">
                                <QuickLink
                                    href="/patients"
                                    label="Patients"
                                    description="View and manage patient records"
                                    icon="🧑‍⚕️"
                                />
                            </Can>
                            <Can permission="appointments.view">
                                <QuickLink
                                    href="/appointments"
                                    label="Appointments"
                                    description="Today's schedule and booking"
                                    icon="📅"
                                />
                            </Can>
                            <Can permission="encounters.view">
                                <QuickLink
                                    href="/encounters"
                                    label="Encounters"
                                    description="Active and recent clinical encounters"
                                    icon="📋"
                                />
                            </Can>
                            <Can permission="medicines.view">
                                <QuickLink
                                    href="/pharmacy/dashboard"
                                    label="Pharmacy"
                                    description="Inventory, stock levels and expiry alerts"
                                    icon="💊"
                                />
                            </Can>
                            <Can permission="prescriptions.view">
                                <QuickLink
                                    href="/prescriptions"
                                    label="Prescriptions"
                                    description="View and manage prescriptions"
                                    icon="📝"
                                />
                            </Can>
                            <Can permission="dispense-records.create">
                                <QuickLink
                                    href="/pharmacy/dispense"
                                    label="Dispense"
                                    description="Point-of-sale dispensing"
                                    icon="🏥"
                                />
                            </Can>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

function StatCard({ label, value, icon }: { label: string; value: string | number; icon: string }) {
    return (
        <div className="rounded-lg border bg-white p-5 flex items-center gap-4">
            <span className="text-3xl">{icon}</span>
            <div>
                <p className="text-2xl font-semibold text-gray-900">{value}</p>
                <p className="text-sm text-gray-500">{label}</p>
            </div>
        </div>
    );
}

function QuickLink({ href, label, description, icon }: {
    href: string;
    label: string;
    description: string;
    icon: string;
}) {
    return (
        <Link
            href={href}
            className="flex items-start gap-3 rounded-lg border p-4 hover:bg-gray-50 transition-colors"
        >
            <span className="text-2xl">{icon}</span>
            <div>
                <p className="text-sm font-medium text-gray-900">{label}</p>
                <p className="text-xs text-gray-500 mt-0.5">{description}</p>
            </div>
        </Link>
    );
}
