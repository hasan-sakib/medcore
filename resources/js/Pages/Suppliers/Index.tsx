import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Pagination } from '@/Components/Pagination';
import type { PageProps, PaginatedResource, Supplier } from '@/types';

interface Props extends PageProps {
    suppliers: PaginatedResource<Supplier>;
}

export default function Index({ suppliers }: Props) {
    const deactivate = (supplier: Supplier) => {
        if (confirm(`Deactivate "${supplier.name}"?`)) {
            router.delete(`/admin/suppliers/${supplier.id}`);
        }
    };

    return (
        <AppLayout>
            <Head title="Suppliers" />
            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold text-gray-900">Suppliers</h1>
                    <Link
                        href="/admin/suppliers/create"
                        className="rounded bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700"
                    >
                        Add Supplier
                    </Link>
                </div>

                <div className="card overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left text-xs text-gray-500">
                            <tr>
                                <th className="px-4 py-2">Name</th>
                                <th className="px-4 py-2">Contact</th>
                                <th className="px-4 py-2">Phone</th>
                                <th className="px-4 py-2">Email</th>
                                <th className="px-4 py-2">Batches</th>
                                <th className="px-4 py-2">Status</th>
                                <th className="px-4 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {suppliers.data.map((supplier) => (
                                <tr key={supplier.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-2 font-medium">
                                        <Link href={`/admin/suppliers/${supplier.id}/edit`} className="text-primary-600 hover:underline">
                                            {supplier.name}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-2">{supplier.contact_name ?? '—'}</td>
                                    <td className="px-4 py-2">{supplier.phone ?? '—'}</td>
                                    <td className="px-4 py-2">{supplier.email ?? '—'}</td>
                                    <td className="px-4 py-2">{supplier.medicine_batches_count ?? 0}</td>
                                    <td className="px-4 py-2">
                                        <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${supplier.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}`}>
                                            {supplier.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </td>
                                    <td className="px-4 py-2 text-right space-x-3">
                                        <Link href={`/admin/suppliers/${supplier.id}/edit`} className="text-sm text-primary-600 hover:underline">
                                            Edit
                                        </Link>
                                        {supplier.is_active && (
                                            <button type="button" onClick={() => deactivate(supplier)} className="text-sm text-danger-600 hover:underline">
                                                Deactivate
                                            </button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                            {suppliers.data.length === 0 && (
                                <tr><td colSpan={7} className="px-4 py-8 text-center text-gray-400">No suppliers found.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination data={suppliers} />
            </div>
        </AppLayout>
    );
}
