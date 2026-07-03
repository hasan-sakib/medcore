import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import type { Encounter, Medicine, PageProps, Patient } from '@/types';

interface PrescriptionItemInput {
    medicine_id: string;
    dosage_instruction: string;
    frequency: string;
    quantity_prescribed: string;
    duration_days: string;
    notes: string;
}

interface Props extends PageProps {
    medicines: Medicine[];
    patient: Patient | null;
    encounter: Encounter | null;
}

export default function Create({ medicines, patient, encounter }: Props) {
    const [items, setItems] = useState<PrescriptionItemInput[]>([
        { medicine_id: '', dosage_instruction: '', frequency: '', quantity_prescribed: '', duration_days: '', notes: '' },
    ]);

    const form = useForm<{
        patient_id: string;
        encounter_id: string;
        notes: string;
        items: PrescriptionItemInput[];
    }>({
        patient_id: String(patient?.id ?? ''),
        encounter_id: String(encounter?.id ?? ''),
        notes: '',
        items,
    });

    const addItem = () => {
        const newItems = [...items, { medicine_id: '', dosage_instruction: '', frequency: '', quantity_prescribed: '', duration_days: '', notes: '' }];
        setItems(newItems);
        form.setData('items', newItems);
    };

    const removeItem = (idx: number) => {
        const newItems = items.filter((_, i) => i !== idx);
        setItems(newItems);
        form.setData('items', newItems);
    };

    const updateItem = (idx: number, field: keyof PrescriptionItemInput, value: string) => {
        const newItems = items.map((item, i) => i === idx ? { ...item, [field]: value } : item);
        setItems(newItems);
        form.setData('items', newItems);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/prescriptions');
    };

    return (
        <AppLayout>
            <Head title="New Prescription" />
            <div className="max-w-2xl space-y-4">
                <h1 className="text-xl font-semibold text-gray-900">New Prescription</h1>

                {patient && (
                    <div className="rounded border bg-blue-50 px-4 py-2 text-sm">
                        Patient: <strong>{patient.first_name} {patient.last_name}</strong> ({patient.mrn})
                        {encounter && <span className="ml-2 text-gray-500">— Encounter #{encounter.id}</span>}
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-4 rounded-lg border bg-white p-6">
                    <div>
                        <label className="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea
                            value={form.data.notes}
                            onChange={(e) => form.setData('notes', e.target.value)}
                            rows={2}
                            className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                        />
                    </div>

                    <div className="space-y-3">
                        <div className="flex items-center justify-between">
                            <h2 className="text-sm font-semibold text-gray-700">Prescription Items</h2>
                            <button type="button" onClick={addItem} className="text-xs text-primary-600 hover:underline">+ Add Item</button>
                        </div>

                        {items.map((item, idx) => (
                            <div key={idx} className="rounded border p-3 space-y-2">
                                <div className="flex justify-between">
                                    <span className="text-xs font-medium text-gray-500">Item {idx + 1}</span>
                                    {items.length > 1 && (
                                        <button type="button" onClick={() => removeItem(idx)} className="text-xs text-red-500 hover:underline">Remove</button>
                                    )}
                                </div>

                                <select
                                    value={item.medicine_id}
                                    onChange={(e) => updateItem(idx, 'medicine_id', e.target.value)}
                                    className="w-full rounded border border-gray-300 px-2 py-1.5 text-sm"
                                >
                                    <option value="">Select medicine…</option>
                                    {medicines.map((m) => (
                                        <option key={m.id} value={m.id}>{m.name} ({m.strength ?? m.unit_type})</option>
                                    ))}
                                </select>

                                <div className="grid grid-cols-3 gap-2">
                                    <input
                                        type="text"
                                        placeholder="Dosage instruction"
                                        value={item.dosage_instruction}
                                        onChange={(e) => updateItem(idx, 'dosage_instruction', e.target.value)}
                                        className="col-span-2 rounded border border-gray-300 px-2 py-1.5 text-sm"
                                    />
                                    <input
                                        type="text"
                                        placeholder="Frequency (BD, TDS…)"
                                        value={item.frequency}
                                        onChange={(e) => updateItem(idx, 'frequency', e.target.value)}
                                        className="rounded border border-gray-300 px-2 py-1.5 text-sm"
                                    />
                                </div>

                                <div className="grid grid-cols-2 gap-2">
                                    <input
                                        type="number"
                                        min="1"
                                        placeholder="Quantity"
                                        value={item.quantity_prescribed}
                                        onChange={(e) => updateItem(idx, 'quantity_prescribed', e.target.value)}
                                        className="rounded border border-gray-300 px-2 py-1.5 text-sm"
                                    />
                                    <input
                                        type="number"
                                        min="1"
                                        placeholder="Duration (days)"
                                        value={item.duration_days}
                                        onChange={(e) => updateItem(idx, 'duration_days', e.target.value)}
                                        className="rounded border border-gray-300 px-2 py-1.5 text-sm"
                                    />
                                </div>
                            </div>
                        ))}
                    </div>

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="rounded bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50"
                    >
                        {form.processing ? 'Creating…' : 'Create Prescription'}
                    </button>
                </form>
            </div>
        </AppLayout>
    );
}
