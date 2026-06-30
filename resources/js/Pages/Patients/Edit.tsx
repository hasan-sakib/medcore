import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps, Patient, Department } from '@/types';

interface Props extends PageProps {
    patient: Patient;
    departments: Department[];
}

export default function Edit({ patient, departments }: Props) {
    const form = useForm({
        first_name: patient.first_name,
        last_name: patient.last_name,
        date_of_birth: patient.date_of_birth,
        gender: patient.gender ?? '',
        national_id: patient.national_id ?? '',
        phone: patient.phone ?? '',
        email: patient.email ?? '',
        address: patient.address ?? '',
        blood_group: patient.blood_group ?? '',
        emergency_contact: patient.emergency_contact ?? '',
        department_id: String(patient.department_id ?? ''),
        status: patient.status,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.patch(`/patients/${patient.id}`);
    };

    const field = (name: keyof typeof form.data, label: string, type = 'text') => (
        <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
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
            <Head title={`Edit ${patient.first_name} ${patient.last_name}`} />
            <div className="max-w-2xl">
                <h1 className="text-xl font-semibold text-gray-900 mb-6">
                    Edit Patient — {patient.mrn}
                </h1>

                <form onSubmit={submit} className="bg-white rounded-lg border border-gray-200 p-6 space-y-5">
                    <div className="grid grid-cols-2 gap-4">
                        {field('first_name', 'First Name')}
                        {field('last_name', 'Last Name')}
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        {field('date_of_birth', 'Date of Birth', 'date')}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                            <select
                                value={form.data.gender}
                                onChange={e => form.setData('gender', e.target.value)}
                                className="w-full rounded border border-gray-300 px-3 py-2 text-sm"
                            >
                                <option value="">Select…</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
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
                            <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select
                                value={form.data.status}
                                onChange={e => form.setData('status', e.target.value as Patient['status'])}
                                className="w-full rounded border border-gray-300 px-3 py-2 text-sm"
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="deceased">Deceased</option>
                            </select>
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        {field('emergency_contact', 'Emergency Contact')}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Department</label>
                            <select
                                value={form.data.department_id}
                                onChange={e => form.setData('department_id', e.target.value)}
                                className="w-full rounded border border-gray-300 px-3 py-2 text-sm"
                            >
                                <option value="">None</option>
                                {departments.map(d => (
                                    <option key={d.id} value={d.id}>{d.name}</option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <div className="flex gap-3 pt-2">
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-60"
                        >
                            {form.processing ? 'Saving…' : 'Save Changes'}
                        </button>
                        <a href={`/patients/${patient.id}`} className="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
