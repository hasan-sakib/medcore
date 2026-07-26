import { useEffect, useState } from 'react';
import type { BedWithAllocation } from '@/types';

interface BedStatusPayload {
    id: number;
    bed_number: string;
    bed_type: string;
    ward_id: number;
    room_id: number | null;
    status: string;
    patient?: {
        id: number;
        name: string;
        admitted_at: string;
    } | null;
}

export function useBedBoard(tenantId: number, initialBeds: BedWithAllocation[]) {
    const [beds, setBeds] = useState<Map<number, BedWithAllocation>>(() => {
        const map = new Map<number, BedWithAllocation>();
        initialBeds.forEach(b => map.set(b.id, b));
        return map;
    });

    useEffect(() => {
        if (!window.Echo || !tenantId) return;

        const channel = window.Echo.private(`tenant.${tenantId}.beds`);

        channel.listen('.bed.status.changed', (payload: BedStatusPayload) => {
            setBeds(prev => {
                const next = new Map(prev);
                const existing = next.get(payload.id);
                if (existing) {
                    next.set(payload.id, {
                        ...existing,
                        status: payload.status as BedWithAllocation['status'],
                        current_allocation: payload.patient
                            ? {
                                  ...existing.current_allocation,
                                  patient: {
                                      id: payload.patient.id,
                                      first_name: payload.patient.name.split(' ')[0] ?? '',
                                      last_name: payload.patient.name.split(' ').slice(1).join(' '),
                                  },
                              }
                            : null,
                    });
                }
                return next;
            });
        });

        return () => {
            window.Echo.leave(`tenant.${tenantId}.beds`);
        };
    }, [tenantId]);

    return beds;
}
