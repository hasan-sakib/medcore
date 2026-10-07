import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps, Supplier } from '@/types';

interface Props extends PageProps {
    supplier: Supplier;
}

export default function Edit({ supplier }: Props) {
    const { data, setData, patch, processing, errors } = useForm({
        name: supplier.name,
        contact_name: supplier.contact_name ?? '',
        phone: supplier.phone ?? '',
        email: supplier.email ?? '',
        address: supplier.address ?? '',
        is_active: supplier.is_active,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(`/admin/suppliers/${supplier.id}`);
    };

    return (
        <AppLayout>
            <Head title={`Edit ${supplier.name}`} />
            <div className="max-w-2xl">
                <div className="mb-6">
                    <Link href="/admin/suppliers" className="text-sm text-gray-500 hover:text-gray-700">← Suppliers</Link>
                    <h1 className="mt-2 text-2xl font-semibold text-gray-900">Edit Supplier</h1>
                </div>

                <form onSubmit={submit} className="card space-y-4 p-6">
                    <div>
                        <label htmlFor="name" className="form-label">Name <span className="text-danger-600">*</span></label>
                        <input id="name" type="text" className="form-input" value={data.name}
                            onChange={(e) => setData('name', e.target.value)} autoFocus />
                        {errors.name && <p className="form-error">{errors.name}</p>}
                    </div>

                    <div>
                        <label htmlFor="contact_name" className="form-label">Contact person</label>
                        <input id="contact_name" type="text" className="form-input" value={data.contact_name}
                            onChange={(e) => setData('contact_name', e.target.value)} />
                        {errors.contact_name && <p className="form-error">{errors.contact_name}</p>}
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label htmlFor="phone" className="form-label">Phone</label>
                            <input id="phone" type="text" className="form-input" value={data.phone}
                                onChange={(e) => setData('phone', e.target.value)} />
                            {errors.phone && <p className="form-error">{errors.phone}</p>}
                        </div>
                        <div>
                            <label htmlFor="email" className="form-label">Email</label>
                            <input id="email" type="email" className="form-input" value={data.email}
                                onChange={(e) => setData('email', e.target.value)} />
                            {errors.email && <p className="form-error">{errors.email}</p>}
                        </div>
                    </div>

                    <div>
                        <label htmlFor="address" className="form-label">Address</label>
                        <textarea id="address" rows={3} className="form-input" value={data.address}
                            onChange={(e) => setData('address', e.target.value)} />
                        {errors.address && <p className="form-error">{errors.address}</p>}
                    </div>

                    <label className="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" checked={data.is_active}
                            onChange={(e) => setData('is_active', e.target.checked)}
                            className="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                        Active
                    </label>
                    {errors.is_active && <p className="form-error">{errors.is_active}</p>}

                    <div className="flex items-center justify-end gap-3 pt-2">
                        <Link href="/admin/suppliers" className="text-sm text-gray-600 hover:text-gray-800">Cancel</Link>
                        <button type="submit" disabled={processing} className="btn-primary">
                            {processing ? 'Saving…' : 'Save Changes'}
                        </button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
