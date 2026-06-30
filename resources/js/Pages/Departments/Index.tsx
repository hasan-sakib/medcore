import { Head, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps, Department } from '@/types';

interface Props extends PageProps {
    departments: Department[];
}

export default function Index({ departments }: Props) {
    const [editing, setEditing] = useState<number | null>(null);

    const createForm = useForm({ name: '', code: '', description: '' });
    const editForm = useForm({ name: '', code: '', description: '' });

    const submitCreate = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post('/admin/departments', { onSuccess: () => createForm.reset() });
    };

    const startEdit = (dept: Department) => {
        editForm.setData({ name: dept.name, code: dept.code, description: dept.description ?? '' });
        setEditing(dept.id);
    };

    const submitEdit = (e: React.FormEvent, id: number) => {
        e.preventDefault();
        editForm.patch(`/admin/departments/${id}`, { onSuccess: () => setEditing(null) });
    };

    const deactivate = (dept: Department) => {
        if (confirm(`Deactivate "${dept.name}"?`)) {
            router.delete(`/admin/departments/${dept.id}`);
        }
    };

    return (
        <AppLayout>
            <Head title="Departments" />
            <div className="max-w-3xl space-y-6">
                <h1 className="text-xl font-semibold text-gray-900">Departments</h1>

                {/* Add form */}
                <form onSubmit={submitCreate} className="bg-white border border-gray-200 rounded-lg p-4 flex gap-3">
                    <input
                        type="text"
                        placeholder="Name"
                        value={createForm.data.name}
                        onChange={e => createForm.setData('name', e.target.value)}
                        className="flex-1 rounded border border-gray-300 px-3 py-1.5 text-sm focus:border-primary-500 focus:outline-none"
                    />
                    <input
                        type="text"
                        placeholder="Code (e.g. ER)"
                        value={createForm.data.code}
                        onChange={e => createForm.setData('code', e.target.value.toUpperCase())}
                        className="w-24 rounded border border-gray-300 px-3 py-1.5 text-sm"
                    />
                    <input
                        type="text"
                        placeholder="Description (optional)"
                        value={createForm.data.description}
                        onChange={e => createForm.setData('description', e.target.value)}
                        className="flex-1 rounded border border-gray-300 px-3 py-1.5 text-sm"
                    />
                    <button
                        type="submit"
                        disabled={createForm.processing}
                        className="rounded bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-60"
                    >
                        Add
                    </button>
                </form>

                {/* Table */}
                <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">Name</th>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">Code</th>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">Patients</th>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                                <th className="px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {departments.map(dept => (
                                <tr key={dept.id}>
                                    <td className="px-4 py-3">
                                        {editing === dept.id ? (
                                            <form onSubmit={e => submitEdit(e, dept.id)} className="flex gap-2">
                                                <input
                                                    type="text"
                                                    value={editForm.data.name}
                                                    onChange={e => editForm.setData('name', e.target.value)}
                                                    className="flex-1 rounded border border-gray-300 px-2 py-1 text-sm"
                                                />
                                                <input
                                                    type="text"
                                                    value={editForm.data.code}
                                                    onChange={e => editForm.setData('code', e.target.value.toUpperCase())}
                                                    className="w-20 rounded border border-gray-300 px-2 py-1 text-sm"
                                                />
                                                <button type="submit" className="text-xs text-primary-600 hover:underline">Save</button>
                                                <button type="button" onClick={() => setEditing(null)} className="text-xs text-gray-400 hover:underline">Cancel</button>
                                            </form>
                                        ) : (
                                            <span className="font-medium text-gray-900">{dept.name}</span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 font-mono text-xs text-gray-600">{dept.code}</td>
                                    <td className="px-4 py-3 text-gray-600">{dept.patients_count ?? 0}</td>
                                    <td className="px-4 py-3">
                                        <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${dept.is_active ? 'bg-clinical-50 text-clinical-700' : 'bg-gray-100 text-gray-500'}`}>
                                            {dept.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-right space-x-2">
                                        <button onClick={() => startEdit(dept)} className="text-xs text-primary-600 hover:underline">Edit</button>
                                        {dept.is_active && (
                                            <button onClick={() => deactivate(dept)} className="text-xs text-danger-600 hover:underline">Deactivate</button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
