import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import type { Medicine, PageProps, PurchaseOrder, Supplier } from '@/types';

interface Props extends PageProps {
    medicines: Medicine[];
    suppliers: Supplier[];
    purchaseOrders: PurchaseOrder[];
}

export default function BatchIntake({ medicines, suppliers, purchaseOrders, flash }: Props) {
    const form = useForm({
        medicine_id: '',
        batch_number: '',
        lot_number: '',
        quantity: '',
        unit_cost: '',
        expiry_date: '',
        manufactured_date: '',
        supplier_id: '',
        purchase_order_id: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/medicine-batches');
    };

    return (
        <AppLayout>
            <Head title="Record Batch Intake (GRN)" />
            <div className="max-w-2xl space-y-4">
                <h1 className="text-xl font-semibold text-gray-900">Record Batch Intake</h1>

                {flash?.error && (
                    <div className="rounded border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">{flash.error}</div>
                )}

                <form onSubmit={handleSubmit} className="space-y-4 rounded-lg border bg-white p-6">
                    <div>
                        <label className="block text-sm font-medium text-gray-700">Medicine <span className="text-red-500">*</span></label>
                        <select
                            value={form.data.medicine_id}
                            onChange={(e) => form.setData('medicine_id', e.target.value)}
                            className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                        >
                            <option value="">Select medicine…</option>
                            {medicines.map((m) => (
                                <option key={m.id} value={m.id}>{m.name} ({m.sku})</option>
                            ))}
                        </select>
                        {form.errors.medicine_id && <p className="mt-1 text-xs text-red-600">{form.errors.medicine_id}</p>}
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Batch Number <span className="text-red-500">*</span></label>
                            <input
                                type="text"
                                value={form.data.batch_number}
                                onChange={(e) => form.setData('batch_number', e.target.value)}
                                className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                            />
                            {form.errors.batch_number && <p className="mt-1 text-xs text-red-600">{form.errors.batch_number}</p>}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Lot Number</label>
                            <input
                                type="text"
                                value={form.data.lot_number}
                                onChange={(e) => form.setData('lot_number', e.target.value)}
                                className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Quantity <span className="text-red-500">*</span></label>
                            <input
                                type="number"
                                min="1"
                                value={form.data.quantity}
                                onChange={(e) => form.setData('quantity', e.target.value)}
                                className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                            />
                            {form.errors.quantity && <p className="mt-1 text-xs text-red-600">{form.errors.quantity}</p>}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Unit Cost</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                value={form.data.unit_cost}
                                onChange={(e) => form.setData('unit_cost', e.target.value)}
                                className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Expiry Date <span className="text-red-500">*</span></label>
                            <input
                                type="date"
                                value={form.data.expiry_date}
                                onChange={(e) => form.setData('expiry_date', e.target.value)}
                                className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                            />
                            {form.errors.expiry_date && <p className="mt-1 text-xs text-red-600">{form.errors.expiry_date}</p>}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Manufactured Date</label>
                            <input
                                type="date"
                                value={form.data.manufactured_date}
                                onChange={(e) => form.setData('manufactured_date', e.target.value)}
                                className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Supplier</label>
                            <select
                                value={form.data.supplier_id}
                                onChange={(e) => form.setData('supplier_id', e.target.value)}
                                className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                            >
                                <option value="">None</option>
                                {suppliers.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Purchase Order</label>
                            <select
                                value={form.data.purchase_order_id}
                                onChange={(e) => form.setData('purchase_order_id', e.target.value)}
                                className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                            >
                                <option value="">None</option>
                                {purchaseOrders.map((po) => <option key={po.id} value={po.id}>{po.po_number}</option>)}
                            </select>
                        </div>
                    </div>

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="rounded bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50"
                    >
                        {form.processing ? 'Recording…' : 'Record Batch'}
                    </button>
                </form>
            </div>
        </AppLayout>
    );
}
