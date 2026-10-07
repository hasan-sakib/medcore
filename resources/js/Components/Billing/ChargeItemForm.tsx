import { Link, useForm } from '@inertiajs/react';
import type { ChargeItem } from '@/types';

interface Props {
    item?: ChargeItem;
    categories: string[];
}

export function ChargeItemForm({ item, categories }: Props) {
    const form = useForm({
        name:       item?.name ?? '',
        code:       item?.code ?? '',
        category:   item?.category ?? 'consultation',
        unit_price: item ? String(item.unit_price) : '',
        tax_rate:   item ? String(item.tax_rate) : '0',
        is_active:  item?.is_active ?? true,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (item) {
            form.put(`/billing/charge-items/${item.id}`);
        } else {
            form.post('/billing/charge-items');
        }
    };

    return (
        <form onSubmit={submit} className="card p-6 space-y-4">
            <div>
                <label className="form-label" htmlFor="ci-name">Name</label>
                <input id="ci-name" type="text" className="form-input" value={form.data.name} maxLength={200}
                    onChange={e => form.setData('name', e.target.value)} required />
                {form.errors.name && <p className="form-error">{form.errors.name}</p>}
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div>
                    <label className="form-label" htmlFor="ci-code">Code</label>
                    <input id="ci-code" type="text" className="form-input font-mono uppercase" value={form.data.code} maxLength={50}
                        onChange={e => form.setData('code', e.target.value.toUpperCase())} required />
                    {form.errors.code && <p className="form-error">{form.errors.code}</p>}
                </div>
                <div>
                    <label className="form-label" htmlFor="ci-category">Category</label>
                    <select id="ci-category" className="form-input capitalize" value={form.data.category}
                        onChange={e => form.setData('category', e.target.value as ChargeItem['category'])}>
                        {categories.map(c => <option key={c} value={c}>{c}</option>)}
                    </select>
                    {form.errors.category && <p className="form-error">{form.errors.category}</p>}
                </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div>
                    <label className="form-label" htmlFor="ci-price">Unit price ($)</label>
                    <input id="ci-price" type="number" step="0.01" min="0" className="form-input" value={form.data.unit_price}
                        onChange={e => form.setData('unit_price', e.target.value)} required />
                    {form.errors.unit_price && <p className="form-error">{form.errors.unit_price}</p>}
                </div>
                <div>
                    <label className="form-label" htmlFor="ci-tax">Tax rate (%)</label>
                    <input id="ci-tax" type="number" step="0.01" min="0" max="100" className="form-input" value={form.data.tax_rate}
                        onChange={e => form.setData('tax_rate', e.target.value)} />
                    {form.errors.tax_rate && <p className="form-error">{form.errors.tax_rate}</p>}
                </div>
            </div>

            <label className="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" checked={form.data.is_active}
                    onChange={e => form.setData('is_active', e.target.checked)}
                    className="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                Active (available when adding invoice lines)
            </label>

            <div className="flex justify-end gap-3 pt-2">
                <Link href="/billing/charge-items" className="btn-secondary">Cancel</Link>
                <button type="submit" disabled={form.processing} className="btn-primary">
                    {item ? 'Save changes' : 'Create charge item'}
                </button>
            </div>
        </form>
    );
}
