import { Head, Link } from '@inertiajs/react';
import PortalLayout from '@/Layouts/PortalLayout';
import type { PageProps, Patient, Appointment, Invoice } from '@/types';

interface Props extends PageProps {
    patient: Patient;
    upcomingAppointments: (Appointment & {
        doctor?: { name: string };
        department?: { name: string } | null;
    })[];
    recentInvoices: Invoice[];
    summary: { total_appointments: number; upcoming_count: number; unpaid_balance: number };
}

const APPT_STATUS_CHIP: Record<string, string> = {
    pending:    'bg-yellow-100 text-yellow-700',
    confirmed:  'bg-green-100 text-green-700',
    completed:  'bg-gray-100 text-gray-600',
    cancelled:  'bg-red-100 text-red-600',
};

const INV_STATUS_CHIP: Record<string, string> = {
    draft:          'bg-gray-100 text-gray-600',
    sent:           'bg-blue-100 text-blue-700',
    paid:           'bg-green-100 text-green-700',
    partially_paid: 'bg-yellow-100 text-yellow-700',
};

export default function PortalDashboard({ patient, upcomingAppointments, recentInvoices, summary }: Props) {
    const greeting = new Date().getHours() < 12 ? 'Good morning' : new Date().getHours() < 17 ? 'Good afternoon' : 'Good evening';

    return (
        <PortalLayout>
            <Head title="My Dashboard" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-xl font-semibold text-gray-900">
                        {greeting}, {patient.first_name}
                    </h1>
                    <p className="text-sm text-gray-500 mt-0.5">MRN: {patient.mrn}</p>
                </div>

                {/* KPIs */}
                <div className="grid grid-cols-3 gap-4">
                    <div className="bg-white rounded-xl border border-gray-200 p-4">
                        <p className="text-xs text-gray-500">Total Appointments</p>
                        <p className="text-2xl font-bold text-gray-900 mt-1">{summary.total_appointments}</p>
                    </div>
                    <div className="bg-white rounded-xl border border-gray-200 p-4">
                        <p className="text-xs text-gray-500">Upcoming</p>
                        <p className="text-2xl font-bold text-emerald-600 mt-1">{summary.upcoming_count}</p>
                    </div>
                    <div className="bg-white rounded-xl border border-gray-200 p-4">
                        <p className="text-xs text-gray-500">Outstanding Balance</p>
                        <p className="text-2xl font-bold text-red-600 mt-1">
                            ${Number(summary.unpaid_balance).toFixed(2)}
                        </p>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {/* Upcoming appointments */}
                    <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div className="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                            <h2 className="font-semibold text-gray-900 text-sm">Upcoming Appointments</h2>
                            <Link href="/portal/appointments/book" className="text-xs text-emerald-600 hover:underline font-medium">
                                + Book
                            </Link>
                        </div>
                        <div className="divide-y divide-gray-50">
                            {upcomingAppointments.map(appt => (
                                <div key={appt.id} className="px-4 py-3">
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <p className="text-sm font-medium text-gray-900">
                                                {appt.doctor ? `Dr. ${appt.doctor.name}` : '—'}
                                            </p>
                                            {appt.department && (
                                                <p className="text-xs text-gray-400">{appt.department.name}</p>
                                            )}
                                            <p className="text-xs text-gray-500 mt-0.5">
                                                {new Date(appt.scheduled_at).toLocaleString([], {
                                                    weekday: 'short', month: 'short', day: 'numeric',
                                                    hour: '2-digit', minute: '2-digit',
                                                })}
                                            </p>
                                        </div>
                                        <span className={`text-xs px-2 py-0.5 rounded-full capitalize font-medium ${APPT_STATUS_CHIP[appt.status] ?? ''}`}>
                                            {appt.status}
                                        </span>
                                    </div>
                                </div>
                            ))}
                            {upcomingAppointments.length === 0 && (
                                <p className="px-4 py-6 text-center text-xs text-gray-400">
                                    No upcoming appointments. <Link href="/portal/appointments/book" className="text-emerald-600 hover:underline">Book one now</Link>
                                </p>
                            )}
                        </div>
                        <div className="px-4 py-2 border-t border-gray-50">
                            <Link href="/portal/appointments" className="text-xs text-gray-500 hover:text-emerald-600">
                                View all appointments →
                            </Link>
                        </div>
                    </div>

                    {/* Recent invoices */}
                    <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div className="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                            <h2 className="font-semibold text-gray-900 text-sm">Recent Invoices</h2>
                        </div>
                        <div className="divide-y divide-gray-50">
                            {recentInvoices.map(inv => (
                                <div key={inv.id} className="px-4 py-3 flex items-center justify-between">
                                    <div>
                                        <p className="text-xs font-mono font-semibold text-primary-700">{inv.invoice_number}</p>
                                        <p className="text-xs text-gray-400">{new Date(inv.created_at).toLocaleDateString()}</p>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm font-semibold">${Number(inv.total_amount).toFixed(2)}</p>
                                        <span className={`text-xs px-1.5 py-0.5 rounded-full capitalize font-medium ${INV_STATUS_CHIP[inv.status] ?? ''}`}>
                                            {inv.status.replace('_', ' ')}
                                        </span>
                                    </div>
                                </div>
                            ))}
                            {recentInvoices.length === 0 && (
                                <p className="px-4 py-6 text-center text-xs text-gray-400">No invoices yet.</p>
                            )}
                        </div>
                        <div className="px-4 py-2 border-t border-gray-50">
                            <Link href="/portal/invoices" className="text-xs text-gray-500 hover:text-emerald-600">
                                View all invoices →
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </PortalLayout>
    );
}
