import { ReactNode } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { useCan } from '@/hooks/useCan';
import type { PageProps } from '@/types';

interface NavItem {
    label: string;
    href: string;
    icon: ReactNode;
    permission?: string;
    role?: string;
}

interface NavSection {
    heading?: string;
    items: NavItem[];
}

const navIcon = (d: string): ReactNode => (
    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d={d} />
    </svg>
);

const navSections: NavSection[] = [
    {
        items: [
            {
                label: 'Dashboard',
                href: '/dashboard',
                icon: (
                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5}
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                ),
            },
            {
                label: 'Patients',
                href: '/patients',
                permission: 'patients.view',
                icon: (
                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5}
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                ),
            },
            {
                label: 'Appointments',
                href: '/appointments',
                permission: 'appointments.view',
                icon: (
                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5}
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                ),
            },
            {
                label: 'Encounters',
                href: '/encounters',
                permission: 'encounters.view',
                icon: (
                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5}
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                ),
            },
            {
                label: 'Departments',
                href: '/admin/departments',
                role: 'tenant-admin',
                icon: (
                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5}
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                ),
            },
            {
                label: 'Schedules',
                href: '/admin/doctor-schedules',
                role: 'tenant-admin',
                icon: (
                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5}
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                ),
            },
        ],
    },
    {
        heading: 'Pharmacy',
        items: [
            {
                label: 'Dashboard',
                href: '/pharmacy/dashboard',
                permission: 'medicines.view',
                icon: (
                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5}
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                ),
            },
            {
                label: 'Dispense',
                href: '/pharmacy/dispense',
                permission: 'dispense-records.create',
                icon: (
                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5}
                            d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                ),
            },
            {
                label: 'Medicines',
                href: '/medicines',
                permission: 'medicines.view',
                icon: (
                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5}
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                ),
            },
            {
                label: 'Prescriptions',
                href: '/prescriptions',
                permission: 'prescriptions.view',
                icon: (
                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5}
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                ),
            },
            {
                label: 'Stock Log',
                href: '/stock-movements',
                permission: 'stock-movements.view',
                icon: (
                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5}
                            d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                ),
            },
        ],
    },
    {
        heading: 'Billing',
        items: [
            {
                label: 'Invoices',
                href: '/invoices',
                permission: 'invoices.view',
                icon: (
                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5}
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                ),
            },
            {
                label: 'Claims',
                href: '/claims',
                permission: 'claims.view',
                icon: (
                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5}
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                ),
            },
            {
                label: 'Analytics',
                href: '/analytics',
                permission: 'reports.view',
                icon: (
                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5}
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                ),
            },
        ],
    },
    {
        heading: 'Resource Management',
        items: [
            {
                label: 'Bed Board',
                href: '/beds/board',
                permission: 'beds.view',
                icon: (
                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5}
                            d="M3 12h18M3 6h18M3 18h18M7 6v12M17 6v12" />
                    </svg>
                ),
            },
            {
                label: 'Operating Rooms',
                href: '/operating-rooms',
                permission: 'operating-rooms.view',
                icon: (
                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5}
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6M4.929 4.929l1.414 1.414M17.657 17.657l1.414 1.414M4.929 19.071l1.414-1.414M17.657 6.343l1.414-1.414" />
                    </svg>
                ),
            },
        ],
    },
    {
        heading: 'Inventory',
        items: [
            { label: 'Receive Stock', href: '/medicine-batches', permission: 'medicine-batches.create',
                icon: navIcon('M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4') },
            { label: 'Suppliers', href: '/admin/suppliers', role: 'tenant-admin',
                icon: navIcon('M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z') },
            { label: 'Purchase Orders', href: '/admin/purchase-orders', role: 'tenant-admin',
                icon: navIcon('M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z') },
        ],
    },
    {
        heading: 'Billing Setup',
        items: [
            { label: 'Charge Items', href: '/billing/charge-items', permission: 'charge-items.manage',
                icon: navIcon('M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z') },
            { label: 'Tax Configuration', href: '/billing/tax-configs', permission: 'tax-configs.manage',
                icon: navIcon('M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2v16z') },
            { label: 'Insurance Policies', href: '/billing/insurance-policies', permission: 'invoices.view',
                icon: navIcon('M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z') },
        ],
    },
    {
        heading: 'Administration',
        items: [
            { label: 'Users', href: '/admin/users', permission: 'users.view',
                icon: navIcon('M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z') },
            { label: 'Roles', href: '/admin/roles', permission: 'roles.manage',
                icon: navIcon('M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z') },
            { label: 'Wards, Rooms & Beds', href: '/admin/facilities', permission: 'wards.manage',
                icon: navIcon('M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6') },
            { label: 'Manage Operating Rooms', href: '/admin/facilities/operating-rooms', permission: 'operating-rooms.manage',
                icon: navIcon('M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z') },
        ],
    },
    {
        heading: 'Platform',
        items: [
            { label: 'Tenants', href: '/super-admin/tenants', role: 'super-admin',
                icon: navIcon('M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4') },
        ],
    },
    {
        heading: 'Account',
        items: [
            { label: 'Security (2FA)', href: '/profile/security',
                icon: navIcon('M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4') },
        ],
    },
];

export default function AppLayout({ children }: { children: ReactNode }) {
    const { auth, tenant, flash } = usePage<PageProps>().props;
    const { can, hasRole } = useCan();

    const isVisible = (item: NavItem) => {
        if (!item.permission && !item.role) return true;
        if (item.permission && can(item.permission)) return true;
        if (item.role && hasRole(item.role)) return true;
        return false;
    };

    return (
        <div className="min-h-screen bg-gray-50 flex">
            {/* Sidebar */}
            <aside className="hidden lg:flex lg:flex-col lg:w-64 bg-white border-r border-gray-200">
                {/* Logo */}
                <div className="flex items-center gap-3 px-6 py-5 border-b border-gray-200">
                    <div className="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
                        <span className="text-white font-bold text-sm">M</span>
                    </div>
                    <div>
                        <p className="font-semibold text-gray-900 text-sm">MedCore</p>
                        {tenant && <p className="text-xs text-gray-500 truncate">{tenant.name}</p>}
                    </div>
                </div>

                {/* Nav */}
                <nav className="flex-1 px-4 py-4 space-y-4 overflow-y-auto">
                    {navSections.map((section, si) => {
                        const visible = section.items.filter(isVisible);
                        if (visible.length === 0) return null;
                        return (
                            <div key={si}>
                                {section.heading && (
                                    <p className="px-3 mb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                        {section.heading}
                                    </p>
                                )}
                                <div className="space-y-1">
                                    {visible.map((item) => (
                                        <Link
                                            key={item.href}
                                            href={item.href}
                                            className="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors"
                                        >
                                            {item.icon}
                                            {item.label}
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        );
                    })}
                </nav>

                {/* User */}
                {auth.user && (
                    <div className="px-4 py-4 border-t border-gray-200">
                        <div className="flex items-center gap-3">
                            <div className="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                                <span className="text-primary-700 font-medium text-sm">
                                    {auth.user.name.charAt(0).toUpperCase()}
                                </span>
                            </div>
                            <div className="flex-1 min-w-0">
                                <p className="text-sm font-medium text-gray-900 truncate">{auth.user.name}</p>
                                <p className="text-xs text-gray-500 truncate">{auth.user.email}</p>
                            </div>
                            <Link
                                href="/logout"
                                method="post"
                                as="button"
                                className="text-gray-400 hover:text-gray-600"
                                title="Sign out"
                            >
                                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                )}
            </aside>

            {/* Main content */}
            <div className="flex-1 flex flex-col min-w-0">
                {/* Flash messages */}
                {flash.success && (
                    <div className="bg-clinical-50 border-b border-clinical-200 px-6 py-3">
                        <p className="text-sm text-clinical-700">{flash.success}</p>
                    </div>
                )}
                {flash.error && (
                    <div className="bg-danger-50 border-b border-danger-200 px-6 py-3">
                        <p className="text-sm text-danger-700">{flash.error}</p>
                    </div>
                )}

                <main className="flex-1 p-6">
                    {children}
                </main>
            </div>
        </div>
    );
}
