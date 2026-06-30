import { Link } from '@inertiajs/react';
import { StatusBadge } from '@/Components/StatusBadge';
import type { Patient } from '@/types';

interface Props {
    patient: Patient;
    linkable?: boolean;
}

export function PatientCard({ patient, linkable = true }: Props) {
    const name = `${patient.first_name} ${patient.last_name}`;

    const content = (
        <div className="flex items-start justify-between">
            <div>
                <p className="font-medium text-gray-900">{name}</p>
                <p className="text-sm text-gray-500">{patient.mrn}</p>
                {patient.date_of_birth && (
                    <p className="text-sm text-gray-500">DOB: {patient.date_of_birth}</p>
                )}
                {patient.phone && (
                    <p className="text-sm text-gray-500">{patient.phone}</p>
                )}
            </div>
            <StatusBadge status={patient.status} />
        </div>
    );

    if (!linkable) return <div className="p-3">{content}</div>;

    return (
        <Link
            href={`/patients/${patient.id}`}
            className="block p-3 hover:bg-gray-50 transition-colors rounded"
        >
            {content}
        </Link>
    );
}
