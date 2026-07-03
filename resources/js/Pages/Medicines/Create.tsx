import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';

export default function Create(_: PageProps) {
    const form = useForm({
        name: '',
        generic_name: '',
        sku: '',
        category: '',
        unit_type: 'tablet',
        strength: '',
        min_stock_level: '0',
        reorder_level: '10',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/admin/medicines');
    };

    return (
        <AppLayout>
            <Head title="Add Medicine" />
            <div className="max-w-lg space-y-4">
                <h1 className="text-xl font-semibold text-gray-900">Add Medicine</h1>

                <form onSubmit={handleSubmit} className="space-y-4 rounded-lg border bg-white p-6">
                    {(
                        [
                            { field: 'name' as const, label: 'Name', required: true as boolean },
                            { field: 'generic_name' as const, label: 'Generic Name', required: false as boolean },
                            { field: 'sku' as const, label: 'SKU', required: true as boolean },
                            { field: 'category' as const, label: 'Category', required: false as boolean },
                            { field: 'strength' as const, label: 'Strength (e.g. 500mg)', required: false as boolean },
                        ]
                    ).map(({ field, label, required }) => (
                        <div key={field}>
                            <label className="block text-sm font-medium text-gray-700">
                                {label} {required && <span className="text-red-500">*</span>}
                            </label>
                            <input
                                type="text"
                                value={form.data[field]}
                                onChange={(e) => form.setData(field, e.target.value)}
                                className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                            />
                            {form.errors[field] && <p className="mt-1 text-xs text-red-600">{form.errors[field]}</p>}
                        </div>
                    ))}

                    <div>
                        <label className="block text-sm font-medium text-gray-700">Unit Type <span className="text-red-500">*</span></label>
                        <select
                            value={form.data.unit_type}
                            onChange={(e) => form.setData('unit_type', e.target.value)}
                            className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                        >
                            {['tablet', 'capsule', 'ml', 'unit', 'vial'].map((u) => (
                                <option key={u} value={u}>{u}</option>
                            ))}
                        </select>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Min Stock Level</label>
                            <input type="number" min="0" value={form.data.min_stock_level}
                                onChange={(e) => form.setData('min_stock_level', e.target.value)}
                                className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none" />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Reorder Level</label>
                            <input type="number" min="0" value={form.data.reorder_level}
                                onChange={(e) => form.setData('reorder_level', e.target.value)}
                                className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none" />
                        </div>
                    </div>

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="rounded bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50"
                    >
                        {form.processing ? 'Saving…' : 'Save Medicine'}
                    </button>
                </form>
            </div>
        </AppLayout>
    );
}
