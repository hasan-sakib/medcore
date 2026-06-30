import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function TenantsCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        slug: '',
        plan: 'trial',
        admin_name: '',
        admin_email: '',
        admin_password: '',
    });

    const autoSlug = (name: string) => {
        setData((prev) => ({
            ...prev,
            name,
            slug: prev.slug || name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, ''),
        }));
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/super-admin/tenants');
    };

    return (
        <AppLayout>
            <Head title="New Tenant" />

            <div className="max-w-2xl">
                {/* Header */}
                <div className="mb-6">
                    <Link href="/super-admin/tenants" className="text-sm text-gray-500 hover:text-gray-700">
                        ← Tenants
                    </Link>
                    <h1 className="text-2xl font-semibold text-gray-900 mt-2">Provision New Tenant</h1>
                    <p className="text-sm text-gray-500 mt-1">
                        Creates the hospital record, seeds default roles/permissions, and provisions the Tenant Admin account.
                    </p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    {/* Tenant info */}
                    <div className="card p-6 space-y-4">
                        <h2 className="font-medium text-gray-900">Hospital / Clinic Info</h2>

                        <div>
                            <label htmlFor="name" className="form-label">Organization name</label>
                            <input id="name" type="text" className="form-input" value={data.name}
                                onChange={(e) => autoSlug(e.target.value)} autoFocus />
                            {errors.name && <p className="form-error">{errors.name}</p>}
                        </div>

                        <div>
                            <label htmlFor="slug" className="form-label">Subdomain slug</label>
                            <div className="flex rounded-lg shadow-sm">
                                <input id="slug" type="text" className="form-input rounded-r-none" value={data.slug}
                                    onChange={(e) => setData('slug', e.target.value)} />
                                <span className="inline-flex items-center px-3 rounded-r-lg border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                    .medcore.local
                                </span>
                            </div>
                            {errors.slug && <p className="form-error">{errors.slug}</p>}
                        </div>

                        <div>
                            <label htmlFor="plan" className="form-label">Subscription plan</label>
                            <select id="plan" className="form-input" value={data.plan}
                                onChange={(e) => setData('plan', e.target.value)}>
                                <option value="trial">Trial (30 days)</option>
                                <option value="basic">Basic</option>
                                <option value="professional">Professional</option>
                                <option value="enterprise">Enterprise</option>
                            </select>
                        </div>
                    </div>

                    {/* Tenant admin */}
                    <div className="card p-6 space-y-4">
                        <h2 className="font-medium text-gray-900">Tenant Admin Account</h2>

                        <div>
                            <label htmlFor="admin_name" className="form-label">Full name</label>
                            <input id="admin_name" type="text" className="form-input" value={data.admin_name}
                                onChange={(e) => setData('admin_name', e.target.value)} />
                            {errors.admin_name && <p className="form-error">{errors.admin_name}</p>}
                        </div>

                        <div>
                            <label htmlFor="admin_email" className="form-label">Email</label>
                            <input id="admin_email" type="email" className="form-input" value={data.admin_email}
                                onChange={(e) => setData('admin_email', e.target.value)} />
                            {errors.admin_email && <p className="form-error">{errors.admin_email}</p>}
                        </div>

                        <div>
                            <label htmlFor="admin_password" className="form-label">
                                Temporary password <span className="text-gray-400">(min 12 chars)</span>
                            </label>
                            <input id="admin_password" type="password" className="form-input" value={data.admin_password}
                                onChange={(e) => setData('admin_password', e.target.value)} />
                            {errors.admin_password && <p className="form-error">{errors.admin_password}</p>}
                        </div>
                    </div>

                    {/* Actions */}
                    <div className="flex items-center gap-3">
                        <button type="submit" disabled={processing} className="btn-primary">
                            {processing ? 'Provisioning…' : 'Provision Tenant'}
                        </button>
                        <Link href="/super-admin/tenants" className="btn-secondary">Cancel</Link>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
