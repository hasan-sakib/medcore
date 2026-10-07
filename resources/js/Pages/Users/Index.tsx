import { FormEvent } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Pagination } from '@/Components/Pagination';
import { Can } from '@/Components/Can';
import type { PageProps, PaginatedResource } from '@/types';

interface StaffUser {
    id: number;
    name: string;
    email: string;
    roles: string[];
    is_active: boolean;
    two_factor_enabled: boolean;
    is_self: boolean;
}

interface Props extends PageProps {
    users: PaginatedResource<StaffUser>;
    filters: { search?: string };
}

const roleLabel = (role: string) => role.replace(/-/g, ' ').replace(/^./, (c) => c.toUpperCase());

export default function UsersIndex({ users, filters }: Props) {
    const search = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const value = (e.currentTarget.elements.namedItem('search') as HTMLInputElement).value;
        router.get('/admin/users', { search: value }, { preserveState: true, replace: true });
    };

    const deactivate = (user: StaffUser) => {
        if (confirm(`Deactivate ${user.name}? They will no longer be able to sign in.`)) {
            router.delete(`/admin/users/${user.id}`);
        }
    };

    const reactivate = (user: StaffUser) => {
        router.post(`/admin/users/${user.id}/restore`);
    };

    return (
        <AppLayout>
            <Head title="Users" />
            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold text-gray-900">Users</h1>
                    <Can permission="users.create">
                        <Link href="/admin/users/create" className="btn-primary">Add user</Link>
                    </Can>
                </div>

                <form onSubmit={search} className="flex gap-2 max-w-md">
                    <input type="text" name="search" defaultValue={filters.search ?? ''}
                        placeholder="Search by name or email…" className="form-input" />
                    <button type="submit" className="btn-secondary">Search</button>
                </form>

                <div className="card overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">Name</th>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">Email</th>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">Role</th>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">2FA</th>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                                <th className="px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {users.data.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-gray-400">No users found</td>
                                </tr>
                            )}
                            {users.data.map((user) => (
                                <tr key={user.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3 font-medium text-gray-900">
                                        {user.name}
                                        {user.is_self && <span className="ml-2 text-xs text-gray-400">(you)</span>}
                                    </td>
                                    <td className="px-4 py-3 text-gray-600">{user.email}</td>
                                    <td className="px-4 py-3 text-gray-600">
                                        {user.roles.length ? user.roles.map(roleLabel).join(', ') : '—'}
                                    </td>
                                    <td className="px-4 py-3 text-gray-600">{user.two_factor_enabled ? 'On' : 'Off'}</td>
                                    <td className="px-4 py-3">
                                        <span className={user.is_active ? 'badge-active' : 'badge-suspended'}>
                                            {user.is_active ? 'Active' : 'Deactivated'}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-right space-x-3 whitespace-nowrap">
                                        <Can permission="users.edit">
                                            <Link href={`/admin/users/${user.id}/edit`} className="text-xs text-primary-600 hover:underline">Edit</Link>
                                            {!user.is_active && (
                                                <button onClick={() => reactivate(user)} className="text-xs text-primary-600 hover:underline">
                                                    Reactivate
                                                </button>
                                            )}
                                        </Can>
                                        <Can permission="users.delete">
                                            {user.is_active && !user.is_self && (
                                                <button onClick={() => deactivate(user)} className="text-xs text-danger-600 hover:underline">
                                                    Deactivate
                                                </button>
                                            )}
                                        </Can>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <Pagination data={users} />
            </div>
        </AppLayout>
    );
}
