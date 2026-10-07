import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import UserForm from '@/Components/UserForm';
import type { PageProps } from '@/types';

interface Props extends PageProps {
    assignableRoles: string[];
}

export default function UsersCreate({ assignableRoles }: Props) {
    const form = useForm({ name: '', email: '', role: '', password: '', is_active: true });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.post('/admin/users');
    };

    return (
        <AppLayout>
            <Head title="New User" />
            <div className="max-w-2xl">
                <div className="mb-6">
                    <Link href="/admin/users" className="text-sm text-gray-500 hover:text-gray-700">← Users</Link>
                    <h1 className="text-2xl font-semibold text-gray-900 mt-2">New user</h1>
                    <p className="text-sm text-gray-500 mt-1">Create a staff account for your organization and assign its role.</p>
                </div>

                <UserForm
                    mode="create"
                    data={form.data}
                    setData={form.setData}
                    errors={form.errors}
                    processing={form.processing}
                    roles={assignableRoles}
                    onSubmit={submit}
                />
            </div>
        </AppLayout>
    );
}
