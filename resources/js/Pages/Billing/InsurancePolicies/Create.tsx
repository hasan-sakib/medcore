import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { InsurancePolicyForm, type PatientRef } from '@/Components/Billing/InsurancePolicyForm';
import type { PageProps } from '@/types';

interface Props extends PageProps {
    patient: PatientRef | null;
}

export default function CreateInsurancePolicy({ patient }: Props) {
    return (
        <AppLayout>
            <Head title="Add Insurance Policy" />
            <div className="max-w-2xl space-y-6">
                <h1 className="text-xl font-semibold text-gray-900">Add Insurance Policy</h1>
                <InsurancePolicyForm patient={patient} />
            </div>
        </AppLayout>
    );
}
