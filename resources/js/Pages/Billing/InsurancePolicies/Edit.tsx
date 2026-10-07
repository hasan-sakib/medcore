import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { InsurancePolicyForm, type PatientRef } from '@/Components/Billing/InsurancePolicyForm';
import type { PageProps, InsurancePolicy } from '@/types';

interface Props extends PageProps {
    policy: InsurancePolicy & { patient: PatientRef };
}

export default function EditInsurancePolicy({ policy }: Props) {
    return (
        <AppLayout>
            <Head title="Edit Insurance Policy" />
            <div className="max-w-2xl space-y-6">
                <div>
                    <h1 className="text-xl font-semibold text-gray-900">Edit Insurance Policy</h1>
                    <p className="text-sm text-gray-500 font-mono">{policy.policy_number}</p>
                </div>
                <InsurancePolicyForm policy={policy} patient={policy.patient} />
            </div>
        </AppLayout>
    );
}
