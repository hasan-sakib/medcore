interface Props {
    expiryDate: string; // "YYYY-MM-DD"
}

export default function ExpiryBadge({ expiryDate }: Props) {
    const today = new Date();
    const expiry = new Date(expiryDate);
    const diffMs = expiry.getTime() - today.getTime();
    const daysUntil = Math.ceil(diffMs / (1000 * 60 * 60 * 24));

    let classes = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ';
    let label: string;

    if (daysUntil <= 0) {
        classes += 'bg-gray-100 text-gray-600';
        label = 'Expired';
    } else if (daysUntil <= 30) {
        classes += 'bg-red-100 text-red-700';
        label = `${daysUntil}d`;
    } else if (daysUntil <= 90) {
        classes += 'bg-amber-100 text-amber-700';
        label = `${daysUntil}d`;
    } else {
        classes += 'bg-green-100 text-green-700';
        label = expiryDate;
    }

    return <span className={classes} title={expiryDate}>{label}</span>;
}
