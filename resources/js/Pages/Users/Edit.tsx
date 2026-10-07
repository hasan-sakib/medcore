import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import UserForm from '@/Components/UserForm';
import type { PageProps } from '@/types';

interface StaffUser {
    id: number;
    name: string;
    email: string;
    role: string | null;
    is_active: boolean;
    is_self: boolean;
}

interface Props extends PageProps {
    user: StaffUser;
    assignableRoles: string[];
}

export default function UsersEdit({ user, assignableRoles }: Props) {
    const form = useForm({
        name: user.name,
        email: user.email,
        role: user.role ?? '',
        password: '',
        is_active: user.is_active,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.patch(`/admin/users/${user.id}`);
    };

    // Make sure the user's current role stays selectable even if the editor cannot hand it out.
    const roleOptions = user.role && !assignableRoles.includes(user.role)
        ? [user.role, ...assignableRoles]
        : assignableRoles;

    return (
        <AppLayout>
            <Head title={`Edit ${user.name}`} />
            <div className="max-w-2xl">
                <div className="mb-6">
                    <Link href="/admin/users" className="text-sm text-gray-500 hover:text-gray-700">← Users</Link>
                    <h1 className="text-2xl font-semibold text-gray-900 mt-2">Edit user</h1>
                    <p className="text-sm text-gray-500 mt-1">{user.email}</p>
                </div>

                <UserForm
                    mode="edit"
                    data={form.data}
                    setData={form.setData}
                    errors={form.errors}
                    processing={form.processing}
                    roles={roleOptions}
                    isSelf={user.is_self}
                    onSubmit={submit}
                />
            </div>
        </AppLayout>
    );
}
