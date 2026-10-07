import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Can } from '@/Components/Can';
import type { PageProps, Invoice, InvoiceLine, Payment, Claim, ChargeItem, InsurancePolicy, Patient } from '@/types';

type FullInvoice = Invoice & {
    patient: Patient;
    lines: InvoiceLine[];
    payments: (Payment & { recorded_by_user?: { name: string } })[];
    claims: (Claim & { insurance_policy: InsurancePolicy })[];
};

interface Props extends PageProps {
    invoice: FullInvoice;
    chargeItems: ChargeItem[];
    policies: InsurancePolicy[];
}

const STATUS_CHIP: Record<string, string> = {
    draft:           'bg-gray-100 text-gray-700',
    sent:            'bg-blue-100 text-blue-700',
    paid:            'bg-green-100 text-green-700',
    partially_paid:  'bg-yellow-100 text-yellow-700',
    cancelled:       'bg-red-100 text-red-700',
    void:            'bg-gray-100 text-gray-400',
};

export default function InvoiceShow({ invoice, chargeItems, policies }: Props) {
    const [showAddLine, setShowAddLine] = useState(false);
    const [showPayment, setShowPayment] = useState(false);
    const [showClaim, setShowClaim] = useState(false);

    const lineForm = useForm({
        charge_item_id: '',
        description: '',
        quantity: '1',
        unit_price: '',
        tax_rate: '0',
        discount_amount: '0',
    });

    const payForm = useForm({
        amount: String(invoice.amount_due),
        payment_method: 'cash',
        reference_number: '',
        notes: '',
    });

    const claimForm = useForm({
        invoice_id: String(invoice.id),
        insurance_policy_id: '',
        amount_claimed: String(invoice.amount_due),
        notes: '',
    });

    const handleChargeSelect = (id: string) => {
        lineForm.setData('charge_item_id', id);
        if (id) {
            const item = chargeItems.find(c => String(c.id) === id);
            if (item) {
                lineForm.setData('description', item.name);
                lineForm.setData('unit_price', String(item.unit_price));
                lineForm.setData('tax_rate', String(item.tax_rate));
            }
        }
    };

    const canEdit = ['draft', 'sent'].includes(invoice.status);
    const canPay  = ['sent', 'partially_paid'].includes(invoice.status);

    return (
        <AppLayout>
            <Head title={`Invoice ${invoice.invoice_number}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-gray-900 font-mono">{invoice.invoice_number}</h1>
                        <p className="text-sm text-gray-500 mt-0.5">
                            {invoice.patient.first_name} {invoice.patient.last_name} · {invoice.patient.mrn}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <span className={`text-xs font-medium px-2.5 py-1 rounded-full capitalize ${STATUS_CHIP[invoice.status] ?? ''}`}>
                            {invoice.status.replace('_', ' ')}
                        </span>
                        <a href={`/invoices/${invoice.id}/pdf`} target="_blank" className="btn-secondary text-xs">
                            Download PDF
                        </a>
                        {invoice.status === 'draft' && (
                            <form action={`/invoices/${invoice.id}/finalize`} method="POST">
                                <input type="hidden" name="_method" value="PATCH" />
                                <input type="hidden" name="_token" value={document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content} />
                                <button type="submit" className="btn-primary text-xs">Finalize & Send</button>
                            </form>
                        )}
                    </div>
                </div>

                {/* Totals bar */}
                <div className="grid grid-cols-4 gap-3">
                    {[
                        { label: 'Subtotal',   value: invoice.subtotal },
                        { label: 'Tax',        value: invoice.tax_total },
                        { label: 'Total',      value: invoice.total_amount, bold: true },
                        { label: 'Amount Due', value: invoice.amount_due, color: 'text-red-600' },
                    ].map(({ label, value, bold, color }) => (
                        <div key={label} className="bg-white rounded-xl border border-gray-200 p-3">
                            <p className="text-xs text-gray-500">{label}</p>
                            <p className={`text-lg ${bold ? 'font-bold' : 'font-semibold'} ${color ?? 'text-gray-900'}`}>
                                ${Number(value).toFixed(2)}
                            </p>
                        </div>
                    ))}
                </div>

                {/* Line items */}
                <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div className="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                        <h2 className="font-semibold text-gray-900 text-sm">Line Items</h2>
                        {canEdit && (
                            <button onClick={() => setShowAddLine(v => !v)} className="text-xs btn-secondary">
                                + Add Item
                            </button>
                        )}
                    </div>

                    {showAddLine && (
                        <div className="p-4 bg-gray-50 border-b border-gray-100">
                            <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <div className="sm:col-span-3">
                                    <label className="form-label">Charge Item (optional)</label>
                                    <select className="form-input" value={lineForm.data.charge_item_id} onChange={e => handleChargeSelect(e.target.value)}>
                                        <option value="">Custom description…</option>
                                        {chargeItems.map(c => <option key={c.id} value={c.id}>{c.name} (${c.unit_price})</option>)}
                                    </select>
                                </div>
                                <div className="sm:col-span-2">
                                    <label className="form-label">Description</label>
                                    <input type="text" className="form-input" value={lineForm.data.description} onChange={e => lineForm.setData('description', e.target.value)} required />
                                </div>
                                <div>
                                    <label className="form-label">Qty</label>
                                    <input type="number" className="form-input" step="0.001" min="0" value={lineForm.data.quantity} onChange={e => lineForm.setData('quantity', e.target.value)} />
                                </div>
                                <div>
                                    <label className="form-label">Unit Price</label>
                                    <input type="number" className="form-input" step="0.01" min="0" value={lineForm.data.unit_price} onChange={e => lineForm.setData('unit_price', e.target.value)} required />
                                </div>
                                <div>
                                    <label className="form-label">Tax %</label>
                                    <input type="number" className="form-input" step="0.01" min="0" max="100" value={lineForm.data.tax_rate} onChange={e => lineForm.setData('tax_rate', e.target.value)} />
                                </div>
                                <div>
                                    <label className="form-label">Discount</label>
                                    <input type="number" className="form-input" step="0.01" min="0" value={lineForm.data.discount_amount} onChange={e => lineForm.setData('discount_amount', e.target.value)} />
                                </div>
                            </div>
                            <div className="flex gap-2 mt-3">
                                <button
                                    onClick={() => lineForm.post(`/invoices/${invoice.id}/lines`, { onSuccess: () => setShowAddLine(false), preserveScroll: true })}
                                    disabled={lineForm.processing}
                                    className="btn-primary text-sm"
                                >
                                    Add
                                </button>
                                <button onClick={() => setShowAddLine(false)} className="btn-secondary text-sm">Cancel</button>
                            </div>
                        </div>
                    )}

                    <table className="min-w-full divide-y divide-gray-100">
                        <thead className="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                            <tr>
                                <th className="px-4 py-2 text-left">Description</th>
                                <th className="px-4 py-2 text-right">Qty</th>
                                <th className="px-4 py-2 text-right">Unit Price</th>
                                <th className="px-4 py-2 text-right">Tax</th>
                                <th className="px-4 py-2 text-right">Discount</th>
                                <th className="px-4 py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-50 text-sm">
                            {invoice.lines.map(line => (
                                <tr key={line.id}>
                                    <td className="px-4 py-2">{line.description}</td>
                                    <td className="px-4 py-2 text-right">{Number(line.quantity)}</td>
                                    <td className="px-4 py-2 text-right">${Number(line.unit_price).toFixed(2)}</td>
                                    <td className="px-4 py-2 text-right">${Number(line.tax_amount).toFixed(2)}</td>
                                    <td className="px-4 py-2 text-right">${Number(line.discount_amount).toFixed(2)}</td>
                                    <td className="px-4 py-2 text-right font-semibold">${Number(line.line_total).toFixed(2)}</td>
                                </tr>
                            ))}
                            {invoice.lines.length === 0 && (
                                <tr><td colSpan={6} className="px-4 py-6 text-center text-xs text-gray-400">No line items yet.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Payments + Claims in 2 columns */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {/* Payments */}
                    <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div className="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                            <h2 className="font-semibold text-gray-900 text-sm">Payments</h2>
                            {canPay && (
                                <button onClick={() => setShowPayment(v => !v)} className="text-xs btn-secondary">+ Record</button>
                            )}
                        </div>
                        {showPayment && (
                            <div className="p-4 bg-gray-50 border-b space-y-3">
                                <div>
                                    <label className="form-label">Amount</label>
                                    <input type="number" className="form-input" step="0.01" value={payForm.data.amount} onChange={e => payForm.setData('amount', e.target.value)} />
                                    {payForm.errors.amount && <p className="form-error">{payForm.errors.amount}</p>}
                                </div>
                                <div>
                                    <label className="form-label">Method</label>
                                    <select className="form-input" value={payForm.data.payment_method} onChange={e => payForm.setData('payment_method', e.target.value)}>
                                        {['cash','card','bank_transfer','insurance','mobile_money','cheque'].map(m => (
                                            <option key={m} value={m}>{m.replace('_',' ')}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="form-label">Reference #</label>
                                    <input type="text" className="form-input" value={payForm.data.reference_number} onChange={e => payForm.setData('reference_number', e.target.value)} />
                                </div>
                                <div className="flex gap-2">
                                    <button
                                        onClick={() => payForm.post(`/invoices/${invoice.id}/payments`, { onSuccess: () => setShowPayment(false), preserveScroll: true })}
                                        disabled={payForm.processing}
                                        className="btn-primary text-sm"
                                    >
                                        Record Payment
                                    </button>
                                    <button onClick={() => setShowPayment(false)} className="btn-secondary text-sm">Cancel</button>
                                </div>
                            </div>
                        )}
                        <div className="divide-y divide-gray-50 text-sm">
                            {invoice.payments.map(p => (
                                <div key={p.id} className="px-4 py-2 flex justify-between">
                                    <div>
                                        <p className="font-medium capitalize">{p.payment_method.replace('_', ' ')}</p>
                                        <p className="text-xs text-gray-400">{new Date(p.paid_at).toLocaleString()}</p>
                                    </div>
                                    <p className="font-semibold text-green-700">${Number(p.amount).toFixed(2)}</p>
                                </div>
                            ))}
                            {invoice.payments.length === 0 && (
                                <p className="px-4 py-6 text-center text-xs text-gray-400">No payments recorded.</p>
                            )}
                        </div>
                    </div>

                    {/* Claims */}
                    <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div className="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                            <h2 className="font-semibold text-gray-900 text-sm">Insurance Claims</h2>
                            {policies.length > 0 && (
                                <button onClick={() => setShowClaim(v => !v)} className="text-xs btn-secondary">+ Claim</button>
                            )}
                        </div>
                        {showClaim && (
                            <div className="p-4 bg-gray-50 border-b space-y-3">
                                <div>
                                    <label className="form-label">Policy</label>
                                    <select className="form-input" value={claimForm.data.insurance_policy_id} onChange={e => claimForm.setData('insurance_policy_id', e.target.value)} required>
                                        <option value="">Select policy…</option>
                                        {policies.map(p => (
                                            <option key={p.id} value={p.id}>{p.provider_name} — {p.policy_number}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="form-label">Amount Claimed</label>
                                    <input type="number" className="form-input" step="0.01" value={claimForm.data.amount_claimed} onChange={e => claimForm.setData('amount_claimed', e.target.value)} />
                                </div>
                                <div className="flex gap-2">
                                    <button
                                        onClick={() => claimForm.post('/claims', { onSuccess: () => setShowClaim(false), preserveScroll: true })}
                                        disabled={claimForm.processing}
                                        className="btn-primary text-sm"
                                    >
                                        Submit Claim
                                    </button>
                                    <button onClick={() => setShowClaim(false)} className="btn-secondary text-sm">Cancel</button>
                                </div>
                            </div>
                        )}
                        <div className="divide-y divide-gray-50 text-sm">
                            {invoice.claims.map(c => (
                                <div key={c.id} className="px-4 py-2">
                                    <div className="flex justify-between">
                                        <p className="font-mono text-xs font-semibold text-primary-700">{c.claim_number}</p>
                                        <span className="text-xs capitalize text-gray-500">{c.status.replace('_', ' ')}</span>
                                    </div>
                                    <p className="text-xs text-gray-400">{c.insurance_policy?.provider_name}</p>
                                    <p className="text-xs mt-0.5">Claimed: <strong>${Number(c.amount_claimed).toFixed(2)}</strong></p>
                                </div>
                            ))}
                            {invoice.claims.length === 0 && (
                                <p className="px-4 py-6 text-center text-xs text-gray-400">No claims filed.</p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
