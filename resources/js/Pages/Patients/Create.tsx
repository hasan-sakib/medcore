import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps, Department } from '@/types';

interface Props extends PageProps {
    departments: Department[];
}

export default function Create({ departments }: Props) {
    const form = useForm({
        first_name: '', last_name: '', date_of_birth: '', gender: '',
        national_id: '', phone: '', email: '', address: '',
        blood_group: '', emergency_contact: '', department_id: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/patients');
    };

    const field = (name: keyof typeof form.data, label: string, type = 'text', required = false) => (
        <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
                {label}{required && <span className="text-danger-500 ml-0.5">*</span>}
            </label>
            <input
                type={type}
                value={form.data[name] as string}
                onChange={e => form.setData(name, e.target.value)}
                className="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
            />
            {form.errors[name] && <p className="mt-1 text-xs text-danger-600">{form.errors[name]}</p>}
        </div>
    );

    return (
        <AppLayout>
            <Head title="Register Patient" />
            <div className="max-w-2xl">
                <div className="mb-6">
                    <h1 className="text-xl font-semibold text-gray-900">Register Patient</h1>
                    <p className="text-sm text-gray-500 mt-1">All PHI is encrypted at rest.</p>
                </div>

                <form onSubmit={submit} className="bg-white rounded-lg border border-gray-200 p-6 space-y-5">
                    <div className="grid grid-cols-2 gap-4">
                        {field('first_name', 'First Name', 'text', true)}
                        {field('last_name', 'Last Name', 'text', true)}
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        {field('date_of_birth', 'Date of Birth', 'date', true)}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                            <select
                                value={form.data.gender}
                                onChange={e => form.setData('gender', e.target.value)}
                                className="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                            >
                                <option value="">Select…</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                            {form.errors.gender && <p className="mt-1 text-xs text-danger-600">{form.errors.gender}</p>}
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        {field('national_id', 'National ID')}
                        {field('phone', 'Phone')}
                    </div>

                    {field('email', 'Email', 'email')}
                    {field('address', 'Address')}

                    <div className="grid grid-cols-2 gap-4">
                        {field('blood_group', 'Blood Group')}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Department</label>
                            <select
                                value={form.data.department_id}
                                onChange={e => form.setData('department_id', e.target.value)}
                                className="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                            >
                                <option value="">None</option>
                                {departments.map(d => (
                                    <option key={d.id} value={d.id}>{d.name}</option>
                                ))}
                            </select>
                        </div>
                    </div>

                    {field('emergency_contact', 'Emergency Contact')}

                    <div className="flex gap-3 pt-2">
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-60"
                        >
                            {form.processing ? 'Registering…' : 'Register Patient'}
                        </button>
                        <a href="/patients" className="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
