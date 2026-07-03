import { Head, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps, PaginatedResource, Prescription, PrescriptionItem } from '@/types';
import { Pagination } from '@/Components/Pagination';

interface Props extends PageProps {
    prescriptions: PaginatedResource<Prescription>;
    filters: { search?: string };
}

export default function Dispense({ prescriptions, filters, flash }: Props) {
    const [selected, setSelected] = useState<Prescription | null>(null);
    const form = useForm({ prescription_item_id: '' });

    const handleDispense = (item: PrescriptionItem) => {
        form.setData('prescription_item_id', String(item.id));
        form.post('/pharmacy/dispense', {
            preserveScroll: true,
            onSuccess: () => setSelected(null),
        });
    };

    const handleSearch = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const search = (e.currentTarget.elements.namedItem('search') as HTMLInputElement).value;
        router.get('/pharmacy/dispense', { search }, { preserveState: true, replace: true });
    };

    return (
        <AppLayout>
            <Head title="Dispense Prescriptions" />
            <div className="space-y-4">
                <h1 className="text-xl font-semibold text-gray-900">Dispense Prescriptions</h1>

                {flash?.error && (
                    <div className="rounded border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">
                        {flash.error}
                    </div>
                )}
                {flash?.success && (
                    <div className="rounded border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-700">
                        {flash.success}
                    </div>
                )}

                <form onSubmit={handleSearch} className="flex gap-2">
                    <input
                        type="text"
                        name="search"
                        defaultValue={filters.search ?? ''}
                        placeholder="Search by MRN..."
                        className="rounded border border-gray-300 px-3 py-1.5 text-sm focus:border-primary-500 focus:outline-none"
                    />
                    <button type="submit" className="rounded bg-gray-100 px-3 py-1.5 text-sm hover:bg-gray-200">Search</button>
                </form>

                <div className="rounded-lg border bg-white">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left text-xs text-gray-500">
                            <tr>
                                <th className="px-4 py-2">Patient</th>
                                <th className="px-4 py-2">Prescribed By</th>
                                <th className="px-4 py-2">Prescribed At</th>
                                <th className="px-4 py-2">Status</th>
                                <th className="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {prescriptions.data.map((rx) => (
                                <>
                                    <tr key={rx.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-2">{rx.patient?.first_name} {rx.patient?.last_name}</td>
                                        <td className="px-4 py-2">{rx.prescribedBy?.name}</td>
                                        <td className="px-4 py-2 text-gray-500">{rx.prescribed_at.slice(0, 10)}</td>
                                        <td className="px-4 py-2">
                                            <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${rx.status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700'}`}>
                                                {rx.status}
                                            </span>
                                        </td>
                                        <td className="px-4 py-2">
                                            <button
                                                onClick={() => setSelected(selected?.id === rx.id ? null : rx)}
                                                className="text-xs text-primary-600 hover:underline"
                                            >
                                                {selected?.id === rx.id ? 'Hide' : 'Dispense'}
                                            </button>
                                        </td>
                                    </tr>
                                    {selected?.id === rx.id && (rx.items ?? []).map((item) => (
                                        <tr key={`item-${item.id}`} className="bg-blue-50">
                                            <td colSpan={4} className="px-8 py-2 text-sm">
                                                <strong>{item.medicine?.name}</strong>
                                                {' '}{item.dosage_instruction} — {item.frequency}
                                                {' '}Qty: {item.quantity_dispensed}/{item.quantity_prescribed}
                                                {' '}{item.medicine?.unit_type}
                                            </td>
                                            <td className="px-4 py-2">
                                                {item.quantity_dispensed < item.quantity_prescribed ? (
                                                    <button
                                                        onClick={() => handleDispense(item)}
                                                        disabled={form.processing}
                                                        className="rounded bg-primary-600 px-2 py-1 text-xs text-white hover:bg-primary-700 disabled:opacity-50"
                                                    >
                                                        {form.processing ? '...' : 'Fill'}
                                                    </button>
                                                ) : (
                                                    <span className="text-xs text-green-600">Filled</span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </>
                            ))}
                            {prescriptions.data.length === 0 && (
                                <tr><td colSpan={5} className="px-4 py-8 text-center text-gray-400">No pending prescriptions.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination data={prescriptions} />
            </div>
        </AppLayout>
    );
}
