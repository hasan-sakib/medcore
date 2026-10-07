import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import type { Medicine, PageProps, Supplier } from '@/types';

interface Props extends PageProps {
    suppliers: Pick<Supplier, 'id' | 'name'>[];
    medicines: Pick<Medicine, 'id' | 'name' | 'sku' | 'unit_type'>[];
}

interface ItemRow {
    medicine_id: string;
    quantity_ordered: string;
    unit_price: string;
}

const emptyItem = (): ItemRow => ({ medicine_id: '', quantity_ordered: '1', unit_price: '0.00' });

export default function Create({ suppliers, medicines }: Props) {
    const { data, setData, post, processing, errors } = useForm<{
        supplier_id: string;
        expected_delivery_date: string;
        notes: string;
        items: ItemRow[];
    }>({
        supplier_id: '',
        expected_delivery_date: '',
        notes: '',
        items: [emptyItem()],
    });

    const itemError = (index: number, field: keyof ItemRow): string | undefined =>
        (errors as Record<string, string | undefined>)[`items.${index}.${field}`];

    const updateItem = (index: number, field: keyof ItemRow, value: string) => {
        setData('items', data.items.map((item: ItemRow, i: number) => (i === index ? { ...item, [field]: value } : item)));
    };

    const addItem = () => setData('items', [...data.items, emptyItem()]);
    const removeItem = (index: number) => setData('items', data.items.filter((_: ItemRow, i: number) => i !== index));

    const total = data.items.reduce(
        (sum: number, item: ItemRow) => sum + (Number(item.quantity_ordered) || 0) * (Number(item.unit_price) || 0),
        0,
    );

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/admin/purchase-orders');
    };

    return (
        <AppLayout>
            <Head title="New Purchase Order" />
            <div className="max-w-4xl">
                <div className="mb-6">
                    <Link href="/admin/purchase-orders" className="text-sm text-gray-500 hover:text-gray-700">← Purchase Orders</Link>
                    <h1 className="mt-2 text-2xl font-semibold text-gray-900">New Purchase Order</h1>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <div className="card space-y-4 p-6">
                        <h2 className="font-medium text-gray-900">Order Details</h2>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label htmlFor="supplier_id" className="form-label">Supplier</label>
                                <select id="supplier_id" className="form-input" value={data.supplier_id}
                                    onChange={(e) => setData('supplier_id', e.target.value)}>
                                    <option value="">— No supplier —</option>
                                    {suppliers.map((s) => (
                                        <option key={s.id} value={s.id}>{s.name}</option>
                                    ))}
                                </select>
                                {errors.supplier_id && <p className="form-error">{errors.supplier_id}</p>}
                            </div>
                            <div>
                                <label htmlFor="expected_delivery_date" className="form-label">Expected delivery date</label>
                                <input id="expected_delivery_date" type="date" className="form-input"
                                    value={data.expected_delivery_date}
                                    onChange={(e) => setData('expected_delivery_date', e.target.value)} />
                                {errors.expected_delivery_date && <p className="form-error">{errors.expected_delivery_date}</p>}
                            </div>
                        </div>
                        <div>
                            <label htmlFor="notes" className="form-label">Notes</label>
                            <textarea id="notes" rows={3} className="form-input" value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)} />
                            {errors.notes && <p className="form-error">{errors.notes}</p>}
                        </div>
                    </div>

                    <div className="card space-y-4 p-6">
                        <div className="flex items-center justify-between">
                            <h2 className="font-medium text-gray-900">Items</h2>
                            <button type="button" onClick={addItem} className="text-sm text-primary-600 hover:underline">
                                + Add item
                            </button>
                        </div>
                        {errors.items && <p className="form-error">{errors.items}</p>}

                        <div className="space-y-3">
                            {data.items.map((item: ItemRow, index: number) => (
                                <div key={index} className="grid grid-cols-12 items-start gap-3">
                                    <div className="col-span-12 sm:col-span-6">
                                        <label className="form-label" htmlFor={`medicine_${index}`}>Medicine</label>
                                        <select id={`medicine_${index}`} className="form-input" value={item.medicine_id}
                                            onChange={(e) => updateItem(index, 'medicine_id', e.target.value)}>
                                            <option value="">Select medicine…</option>
                                            {medicines.map((m) => (
                                                <option key={m.id} value={m.id}>{m.name} ({m.sku}) — {m.unit_type}</option>
                                            ))}
                                        </select>
                                        {itemError(index, 'medicine_id') && <p className="form-error">{itemError(index, 'medicine_id')}</p>}
                                    </div>
                                    <div className="col-span-5 sm:col-span-2">
                                        <label className="form-label" htmlFor={`qty_${index}`}>Quantity</label>
                                        <input id={`qty_${index}`} type="number" min="1" step="1" className="form-input"
                                            value={item.quantity_ordered}
                                            onChange={(e) => updateItem(index, 'quantity_ordered', e.target.value)} />
                                        {itemError(index, 'quantity_ordered') && <p className="form-error">{itemError(index, 'quantity_ordered')}</p>}
                                    </div>
                                    <div className="col-span-5 sm:col-span-3">
                                        <label className="form-label" htmlFor={`price_${index}`}>Unit price</label>
                                        <input id={`price_${index}`} type="number" min="0" step="0.01" className="form-input"
                                            value={item.unit_price}
                                            onChange={(e) => updateItem(index, 'unit_price', e.target.value)} />
                                        {itemError(index, 'unit_price') && <p className="form-error">{itemError(index, 'unit_price')}</p>}
                                    </div>
                                    <div className="col-span-2 sm:col-span-1 pt-6 text-right">
                                        {data.items.length > 1 && (
                                            <button type="button" onClick={() => removeItem(index)}
                                                className="text-sm text-danger-600 hover:underline" aria-label="Remove item">
                                                Remove
                                            </button>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="border-t pt-3 text-right text-sm text-gray-700">
                            Estimated total: <span className="font-semibold">{total.toFixed(2)}</span>
                        </div>
                    </div>

                    <div className="flex items-center justify-end gap-3">
                        <Link href="/admin/purchase-orders" className="text-sm text-gray-600 hover:text-gray-800">Cancel</Link>
                        <button type="submit" disabled={processing} className="btn-primary">
                            {processing ? 'Saving…' : 'Create Purchase Order'}
                        </button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
