import { useState, useEffect } from 'react';
import type { AppointmentSlot } from '@/types';

interface Props {
    doctorId: number | null;
    date: string; // YYYY-MM-DD
    selected: string | null; // ISO datetime
    onSelect: (slot: AppointmentSlot) => void;
    className?: string;
}

function formatTime(iso: string) {
    return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
}

export function AppointmentSlotPicker({ doctorId, date, selected, onSelect, className = '' }: Props) {
    const [slots, setSlots] = useState<AppointmentSlot[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (!doctorId || !date) { setSlots([]); return; }
        setLoading(true);
        setError(null);

        fetch(`/appointments/slots?doctor_id=${doctorId}&date=${date}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(r => r.json())
            .then(data => {
                setSlots(data ?? []);
                setLoading(false);
            })
            .catch(() => {
                setError('Failed to load slots');
                setLoading(false);
            });
    }, [doctorId, date]);

    if (!doctorId || !date) return null;

    return (
        <div className={className}>
            <p className="mb-2 text-sm font-medium text-gray-700">Available Slots</p>
            {loading && <p className="text-sm text-gray-500">Loading…</p>}
            {error && <p className="text-sm text-danger-600">{error}</p>}
            {!loading && !error && slots.length === 0 && (
                <p className="text-sm text-gray-500">No slots available for this day</p>
            )}
            {!loading && slots.length > 0 && (
                <div className="flex flex-wrap gap-2">
                    {slots.map(slot => (
                        <button
                            key={slot.start}
                            type="button"
                            onClick={() => onSelect(slot)}
                            className={`rounded px-3 py-1.5 text-sm font-medium transition-colors border ${
                                selected === slot.start
                                    ? 'bg-primary-600 text-white border-primary-600'
                                    : 'bg-white text-gray-700 border-gray-300 hover:border-primary-500 hover:text-primary-600'
                            }`}
                        >
                            {formatTime(slot.start)}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
