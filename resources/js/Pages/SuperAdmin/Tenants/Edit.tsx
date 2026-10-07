import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import type { Tenant } from '@/types';

interface Props {
    tenant: Tenant;
}

export default function TenantsEdit({ tenant }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: tenant.name,
        status: tenant.status === 'suspended' ? 'suspended' : 'active',
        plan: tenant.subscription_plan ?? 'trial',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(`/super-admin/tenants/${tenant.id}`);
    };

    return (
        <AppLayout>
            <Head title={`Edit ${tenant.name}`} />

            <div className="max-w-2xl">
                <div className="mb-6">
                    <Link href={`/super-admin/tenants/${tenant.id}`} className="text-sm text-gray-500 hover:text-gray-700">
                        ← {tenant.name}
                    </Link>
                    <h1 className="mt-2 text-2xl font-semibold text-gray-900">Edit Tenant</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Slug <code className="rounded bg-gray-100 px-1.5 py-0.5 text-xs">{tenant.slug}</code> cannot be changed.
                    </p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <div className="card space-y-4 p-6">
                        <div>
                            <label htmlFor="name" className="form-label">Organization name</label>
                            <input id="name" type="text" className="form-input" value={data.name}
                                onChange={(e) => setData('name', e.target.value)} autoFocus />
                            {errors.name && <p className="form-error">{errors.name}</p>}
                        </div>

                        <div>
                            <label htmlFor="status" className="form-label">Status</label>
                            <select id="status" className="form-input" value={data.status}
                                onChange={(e) => setData('status', e.target.value)}>
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                            </select>
                            {errors.status && <p className="form-error">{errors.status}</p>}
                        </div>

                        <div>
                            <label htmlFor="plan" className="form-label">Subscription plan</label>
                            <select id="plan" className="form-input" value={data.plan}
                                onChange={(e) => setData('plan', e.target.value)}>
                                <option value="trial">Trial</option>
                                <option value="basic">Basic</option>
                                <option value="professional">Professional</option>
                                <option value="enterprise">Enterprise</option>
                            </select>
                            {errors.plan && <p className="form-error">{errors.plan}</p>}
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <button type="submit" disabled={processing} className="btn-primary">
                            {processing ? 'Saving…' : 'Save Changes'}
                        </button>
                        <Link href={`/super-admin/tenants/${tenant.id}`} className="btn-secondary">Cancel</Link>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
