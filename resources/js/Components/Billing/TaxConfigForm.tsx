import { Link, useForm } from '@inertiajs/react';
import type { TaxConfig } from '@/types';

interface Props {
    config?: TaxConfig;
    appliesTo: string[];
}

export function TaxConfigForm({ config, appliesTo }: Props) {
    const form = useForm({
        name:       config?.name ?? '',
        rate:       config ? String(config.rate) : '',
        applies_to: config?.applies_to ?? 'all',
        is_active:  config?.is_active ?? true,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (config) {
            form.put(`/billing/tax-configs/${config.id}`);
        } else {
            form.post('/billing/tax-configs');
        }
    };

    return (
        <form onSubmit={submit} className="card p-6 space-y-4">
            <div>
                <label className="form-label" htmlFor="tc-name">Name</label>
                <input id="tc-name" type="text" className="form-input" value={form.data.name} maxLength={100}
                    placeholder="e.g. Standard VAT" onChange={e => form.setData('name', e.target.value)} required />
                {form.errors.name && <p className="form-error">{form.errors.name}</p>}
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div>
                    <label className="form-label" htmlFor="tc-rate">Rate (%)</label>
                    <input id="tc-rate" type="number" step="0.01" min="0" max="100" className="form-input" value={form.data.rate}
                        onChange={e => form.setData('rate', e.target.value)} required />
                    {form.errors.rate && <p className="form-error">{form.errors.rate}</p>}
                </div>
                <div>
                    <label className="form-label" htmlFor="tc-applies">Applies to</label>
                    <select id="tc-applies" className="form-input capitalize" value={form.data.applies_to}
                        onChange={e => form.setData('applies_to', e.target.value as TaxConfig['applies_to'])}>
                        {appliesTo.map(a => <option key={a} value={a}>{a}</option>)}
                    </select>
                    {form.errors.applies_to && <p className="form-error">{form.errors.applies_to}</p>}
                </div>
            </div>

            <label className="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" checked={form.data.is_active}
                    onChange={e => form.setData('is_active', e.target.checked)}
                    className="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                Active
            </label>

            <div className="flex justify-end gap-3 pt-2">
                <Link href="/billing/tax-configs" className="btn-secondary">Cancel</Link>
                <button type="submit" disabled={form.processing} className="btn-primary">
                    {config ? 'Save changes' : 'Create tax configuration'}
                </button>
            </div>
        </form>
    );
}
