import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { ChargeItemForm } from '@/Components/Billing/ChargeItemForm';
import type { PageProps, ChargeItem } from '@/types';

interface Props extends PageProps {
    item: ChargeItem;
    categories: string[];
}

export default function EditChargeItem({ item, categories }: Props) {
    return (
        <AppLayout>
            <Head title={`Edit ${item.name}`} />
            <div className="max-w-2xl space-y-6">
                <div>
                    <h1 className="text-xl font-semibold text-gray-900">Edit Charge Item</h1>
                    <p className="text-sm text-gray-500 font-mono">{item.code}</p>
                </div>
                <ChargeItemForm item={item} categories={categories} />
            </div>
        </AppLayout>
    );
}
