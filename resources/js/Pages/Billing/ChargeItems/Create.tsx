import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { ChargeItemForm } from '@/Components/Billing/ChargeItemForm';
import type { PageProps } from '@/types';

interface Props extends PageProps {
    categories: string[];
}

export default function CreateChargeItem({ categories }: Props) {
    return (
        <AppLayout>
            <Head title="New Charge Item" />
            <div className="max-w-2xl space-y-6">
                <h1 className="text-xl font-semibold text-gray-900">New Charge Item</h1>
                <ChargeItemForm categories={categories} />
            </div>
        </AppLayout>
    );
}
