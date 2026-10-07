import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import type { Medicine, PageProps } from '@/types';

interface Props extends PageProps {
    medicine: Medicine;
}

export default function Edit({ medicine }: Props) {
    const form = useForm({
        name: medicine.name,
        generic_name: medicine.generic_name ?? '',
        category: medicine.category ?? '',
        unit_type: medicine.unit_type as string,
        strength: medicine.strength ?? '',
        min_stock_level: String(medicine.min_stock_level ?? 0),
        reorder_level: String(medicine.reorder_level ?? 0),
        is_active: medicine.is_active,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.put(`/admin/medicines/${medicine.id}`);
    };

    const textFields = [
        { field: 'name' as const, label: 'Name', required: true },
        { field: 'generic_name' as const, label: 'Generic Name', required: false },
        { field: 'category' as const, label: 'Category', required: false },
        { field: 'strength' as const, label: 'Strength (e.g. 500mg)', required: false },
    ];

    return (
        <AppLayout>
            <Head title={`Edit ${medicine.name}`} />
            <div className="max-w-lg space-y-4">
                <div>
                    <Link href={`/medicines/${medicine.id}`} className="text-sm text-gray-500 hover:text-gray-700">← {medicine.name}</Link>
                    <h1 className="mt-2 text-xl font-semibold text-gray-900">Edit Medicine</h1>
                </div>

                <form onSubmit={handleSubmit} className="card space-y-4 p-6">
                    <div>
                        <label className="form-label">SKU</label>
                        <input type="text" value={medicine.sku} disabled className="form-input bg-gray-50 text-gray-500" />
                    </div>

                    {textFields.map(({ field, label, required }) => (
                        <div key={field}>
                            <label className="form-label">
                                {label} {required && <span className="text-red-500">*</span>}
                            </label>
                            <input
                                type="text"
                                value={form.data[field]}
                                onChange={(e) => form.setData(field, e.target.value)}
                                className="form-input"
                            />
                            {form.errors[field] && <p className="form-error">{form.errors[field]}</p>}
                        </div>
                    ))}

                    <div>
                        <label className="form-label">Unit Type <span className="text-red-500">*</span></label>
                        <select
                            value={form.data.unit_type}
                            onChange={(e) => form.setData('unit_type', e.target.value)}
                            className="form-input"
                        >
                            {['tablet', 'capsule', 'ml', 'unit', 'vial'].map((u) => (
                                <option key={u} value={u}>{u}</option>
                            ))}
                        </select>
                        {form.errors.unit_type && <p className="form-error">{form.errors.unit_type}</p>}
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="form-label">Min Stock Level</label>
                            <input type="number" min="0" value={form.data.min_stock_level}
                                onChange={(e) => form.setData('min_stock_level', e.target.value)}
                                className="form-input" />
                            {form.errors.min_stock_level && <p className="form-error">{form.errors.min_stock_level}</p>}
                        </div>
                        <div>
                            <label className="form-label">Reorder Level</label>
                            <input type="number" min="0" value={form.data.reorder_level}
                                onChange={(e) => form.setData('reorder_level', e.target.value)}
                                className="form-input" />
                            {form.errors.reorder_level && <p className="form-error">{form.errors.reorder_level}</p>}
                        </div>
                    </div>

                    <label className="flex items-center gap-2 text-sm text-gray-700">
                        <input
                            type="checkbox"
                            checked={form.data.is_active}
                            onChange={(e) => form.setData('is_active', e.target.checked)}
                            className="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                        />
                        Active (available for prescribing and dispensing)
                    </label>

                    <div className="flex items-center gap-3">
                        <button type="submit" disabled={form.processing} className="btn-primary">
                            {form.processing ? 'Saving…' : 'Save Changes'}
                        </button>
                        <Link href={`/medicines/${medicine.id}`} className="btn-secondary">Cancel</Link>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
