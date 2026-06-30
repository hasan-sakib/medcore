type Status =
    | 'pending' | 'confirmed' | 'checked_in' | 'completed' | 'cancelled' | 'no_show'
    | 'open' | 'in_progress' | 'active' | 'inactive' | 'deceased'
    | 'outpatient' | 'inpatient' | 'emergency' | 'teleconsult';

const STATUS_STYLES: Record<string, string> = {
    // Appointment / Encounter statuses
    pending:     'bg-yellow-100 text-yellow-800',
    confirmed:   'bg-blue-100 text-blue-800',
    checked_in:  'bg-indigo-100 text-indigo-800',
    in_progress: 'bg-indigo-100 text-indigo-800',
    completed:   'bg-clinical-50 text-clinical-700',
    open:        'bg-clinical-50 text-clinical-700',
    cancelled:   'bg-gray-100 text-gray-500',
    no_show:     'bg-gray-100 text-gray-500',
    // Patient status
    active:   'bg-clinical-50 text-clinical-700',
    inactive: 'bg-gray-100 text-gray-500',
    deceased: 'bg-gray-200 text-gray-600',
    // Encounter type
    outpatient:  'bg-blue-100 text-blue-700',
    inpatient:   'bg-purple-100 text-purple-700',
    emergency:   'bg-danger-100 text-danger-700',
    teleconsult: 'bg-cyan-100 text-cyan-700',
};

const STATUS_LABELS: Record<string, string> = {
    checked_in:  'Checked In',
    in_progress: 'In Progress',
    no_show:     'No Show',
    teleconsult: 'Teleconsult',
};

interface Props {
    status: Status | string;
    className?: string;
}

export function StatusBadge({ status, className = '' }: Props) {
    const style = STATUS_STYLES[status] ?? 'bg-gray-100 text-gray-600';
    const label = STATUS_LABELS[status] ?? status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());

    return (
        <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${style} ${className}`}>
            {label}
        </span>
    );
}
