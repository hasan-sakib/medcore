import { Head } from '@inertiajs/react';
import {
    BarChart, Bar, XAxis, YAxis, Tooltip, CartesianGrid, ResponsiveContainer,
    PieChart, Pie, Cell,
} from 'recharts';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';

interface MonthlyRevenue { month: string; total: number; }
interface InvoiceSummary {
    total_outstanding: number;
    total_collected: number;
    by_status: Record<string, { count: number; total: number }>;
}
interface EncounterType { type: string; count: number; }
interface BedOccupancy { total: number; occupied: number; wards: { ward: string; total: number; occupied: number; pct: number }[]; }
interface TopMedicine { name: string; qty: number; }
interface ClaimSummary { status: string; count: number; claimed: number; paid: number; }

interface Props extends PageProps {
    monthly_revenue: MonthlyRevenue[];
    invoice_summary: InvoiceSummary;
    encounter_types: EncounterType[];
    bed_occupancy: BedOccupancy;
    top_medicines: TopMedicine[];
    claims_summary: ClaimSummary[];
}

const COLORS = ['#2563eb', '#16a34a', '#d97706', '#dc2626', '#7c3aed', '#0891b2'];

const fmt = (n: number) => `$${n.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;

export default function AnalyticsDashboard({
    monthly_revenue,
    invoice_summary,
    encounter_types,
    bed_occupancy,
    top_medicines,
    claims_summary,
}: Props) {
    const occupancyPct = bed_occupancy.total > 0
        ? Math.round(bed_occupancy.occupied / bed_occupancy.total * 100)
        : 0;

    return (
        <AppLayout>
            <Head title="Analytics" />

            <div className="space-y-6">
                <h1 className="text-xl font-semibold text-gray-900">Analytics Dashboard</h1>

                {/* KPI cards */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="bg-white rounded-xl border border-gray-200 p-4">
                        <p className="text-xs text-gray-500">Outstanding</p>
                        <p className="text-2xl font-bold text-red-600">{fmt(invoice_summary.total_outstanding)}</p>
                    </div>
                    <div className="bg-white rounded-xl border border-gray-200 p-4">
                        <p className="text-xs text-gray-500">Total Collected</p>
                        <p className="text-2xl font-bold text-green-600">{fmt(invoice_summary.total_collected)}</p>
                    </div>
                    <div className="bg-white rounded-xl border border-gray-200 p-4">
                        <p className="text-xs text-gray-500">Bed Occupancy</p>
                        <p className="text-2xl font-bold text-primary-600">{occupancyPct}%</p>
                        <p className="text-xs text-gray-400 mt-0.5">{bed_occupancy.occupied} / {bed_occupancy.total} beds</p>
                    </div>
                    <div className="bg-white rounded-xl border border-gray-200 p-4">
                        <p className="text-xs text-gray-500">Claims (total)</p>
                        <p className="text-2xl font-bold text-gray-900">
                            {claims_summary.reduce((s, c) => s + c.count, 0)}
                        </p>
                    </div>
                </div>

                {/* Monthly Revenue */}
                <div className="bg-white rounded-xl border border-gray-200 p-5">
                    <h2 className="font-semibold text-gray-900 mb-4 text-sm">Monthly Revenue (12 months)</h2>
                    <ResponsiveContainer width="100%" height={220}>
                        <BarChart data={monthly_revenue} margin={{ top: 4, right: 8, left: 0, bottom: 0 }}>
                            <CartesianGrid strokeDasharray="3 3" stroke="#f3f4f6" />
                            <XAxis dataKey="month" tick={{ fontSize: 11 }} />
                            <YAxis tickFormatter={v => `$${(v / 1000).toFixed(0)}k`} tick={{ fontSize: 11 }} />
                            <Tooltip formatter={(v) => fmt(Number(v))} />
                            <Bar dataKey="total" fill="#2563eb" radius={[4, 4, 0, 0]} />
                        </BarChart>
                    </ResponsiveContainer>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {/* Encounter types */}
                    <div className="bg-white rounded-xl border border-gray-200 p-5">
                        <h2 className="font-semibold text-gray-900 mb-4 text-sm">Encounters by Type (last 30 days)</h2>
                        {encounter_types.length > 0 ? (
                            <ResponsiveContainer width="100%" height={200}>
                                <PieChart>
                                    <Pie
                                        data={encounter_types}
                                        dataKey="count"
                                        nameKey="type"
                                        cx="50%"
                                        cy="50%"
                                        outerRadius={75}
                                        label={(entry) => {
                                            const { type, count } = entry as unknown as { type: string; count: number };
                                            return `${type}: ${count}`;
                                        }}
                                    >
                                        {encounter_types.map((_, i) => (
                                            <Cell key={i} fill={COLORS[i % COLORS.length]} />
                                        ))}
                                    </Pie>
                                    <Tooltip />
                                </PieChart>
                            </ResponsiveContainer>
                        ) : (
                            <p className="text-sm text-gray-400 text-center py-8">No encounters in last 30 days.</p>
                        )}
                    </div>

                    {/* Bed occupancy by ward */}
                    <div className="bg-white rounded-xl border border-gray-200 p-5">
                        <h2 className="font-semibold text-gray-900 mb-4 text-sm">Bed Occupancy by Ward</h2>
                        <div className="space-y-3">
                            {bed_occupancy.wards.map(w => (
                                <div key={w.ward}>
                                    <div className="flex justify-between text-xs text-gray-600 mb-1">
                                        <span>{w.ward}</span>
                                        <span>{w.occupied}/{w.total} ({w.pct}%)</span>
                                    </div>
                                    <div className="h-2 bg-gray-100 rounded-full overflow-hidden">
                                        <div
                                            className="h-full rounded-full bg-primary-500"
                                            style={{ width: `${w.pct}%` }}
                                        />
                                    </div>
                                </div>
                            ))}
                            {bed_occupancy.wards.length === 0 && (
                                <p className="text-sm text-gray-400 text-center py-6">No ward data.</p>
                            )}
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {/* Top medicines */}
                    <div className="bg-white rounded-xl border border-gray-200 p-5">
                        <h2 className="font-semibold text-gray-900 mb-4 text-sm">Top Dispensed Medicines (30 days)</h2>
                        {top_medicines.length > 0 ? (
                            <ResponsiveContainer width="100%" height={220}>
                                <BarChart data={top_medicines} layout="vertical" margin={{ top: 0, right: 16, left: 0, bottom: 0 }}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#f3f4f6" />
                                    <XAxis type="number" tick={{ fontSize: 11 }} />
                                    <YAxis dataKey="name" type="category" width={120} tick={{ fontSize: 10 }} />
                                    <Tooltip />
                                    <Bar dataKey="qty" fill="#16a34a" radius={[0, 4, 4, 0]} />
                                </BarChart>
                            </ResponsiveContainer>
                        ) : (
                            <p className="text-sm text-gray-400 text-center py-8">No dispensing data.</p>
                        )}
                    </div>

                    {/* Claims summary */}
                    <div className="bg-white rounded-xl border border-gray-200 p-5">
                        <h2 className="font-semibold text-gray-900 mb-4 text-sm">Claims by Status</h2>
                        <div className="space-y-2">
                            {claims_summary.map(c => (
                                <div key={c.status} className="flex items-center justify-between text-sm">
                                    <span className="capitalize text-gray-700">{c.status.replace('_', ' ')}</span>
                                    <div className="text-right">
                                        <span className="font-semibold">{c.count}</span>
                                        <span className="text-xs text-gray-400 ml-2">{fmt(c.claimed)} claimed</span>
                                    </div>
                                </div>
                            ))}
                            {claims_summary.length === 0 && (
                                <p className="text-sm text-gray-400 text-center py-6">No claims yet.</p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
