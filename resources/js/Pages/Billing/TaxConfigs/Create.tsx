import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { TaxConfigForm } from '@/Components/Billing/TaxConfigForm';
import type { PageProps } from '@/types';

interface Props extends PageProps {
    appliesTo: string[];
}

export default function CreateTaxConfig({ appliesTo }: Props) {
    return (
        <AppLayout>
            <Head title="New Tax Configuration" />
            <div className="max-w-xl space-y-6">
                <h1 className="text-xl font-semibold text-gray-900">New Tax Configuration</h1>
                <TaxConfigForm appliesTo={appliesTo} />
            </div>
        </AppLayout>
    );
}
