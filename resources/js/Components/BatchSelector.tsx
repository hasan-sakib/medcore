import { MedicineBatch } from '@/types';
import ExpiryBadge from './ExpiryBadge';

interface Props {
    batches: MedicineBatch[];
    requiredQty: number;
}

export default function BatchSelector({ batches, requiredQty }: Props) {
    const active = batches
        .filter((b) => b.status === 'active' && b.quantity_on_hand > 0)
        .sort((a, b) => a.expiry_date.localeCompare(b.expiry_date));

    let remaining = requiredQty;

    return (
        <div className="space-y-1">
            <p className="text-xs text-gray-500 mb-2">FEFO deduction order:</p>
            {active.map((batch) => {
                const take = Math.min(remaining, batch.quantity_on_hand);
                remaining -= take;
                return (
                    <div key={batch.id} className="flex items-center gap-3 text-sm">
                        <span className="font-mono text-gray-700">{batch.batch_number}</span>
                        <ExpiryBadge expiryDate={batch.expiry_date} />
                        <span className="text-gray-500">avail: {batch.quantity_on_hand}</span>
                        {take > 0 && (
                            <span className="text-blue-600 font-medium">-{take}</span>
                        )}
                    </div>
                );
            })}
            {active.length === 0 && (
                <p className="text-sm text-red-600">No active stock available.</p>
            )}
        </div>
    );
}
