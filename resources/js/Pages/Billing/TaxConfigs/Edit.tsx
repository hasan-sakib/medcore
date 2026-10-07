import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { TaxConfigForm } from '@/Components/Billing/TaxConfigForm';
import type { PageProps, TaxConfig } from '@/types';

interface Props extends PageProps {
    config: TaxConfig;
    appliesTo: string[];
}

export default function EditTaxConfig({ config, appliesTo }: Props) {
    return (
        <AppLayout>
            <Head title={`Edit ${config.name}`} />
            <div className="max-w-xl space-y-6">
                <h1 className="text-xl font-semibold text-gray-900">Edit Tax Configuration</h1>
                <TaxConfigForm config={config} appliesTo={appliesTo} />
            </div>
        </AppLayout>
    );
}
