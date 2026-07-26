<?php

namespace App\Events;

use App\Models\Bed;
use App\Models\BedAllocation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BedStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Bed $bed,
        public readonly ?BedAllocation $allocation = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.'.$this->bed->tenant_id.'.beds'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'bed.status.changed';
    }

    public function broadcastWith(): array
    {
        $payload = [
            'id'         => $this->bed->id,
            'bed_number' => $this->bed->bed_number,
            'bed_type'   => $this->bed->bed_type,
            'ward_id'    => $this->bed->ward_id,
            'room_id'    => $this->bed->room_id,
            'status'     => $this->bed->status,
        ];

        if ($this->allocation) {
            $payload['patient'] = [
                'id'         => $this->allocation->patient_id,
                'name'       => optional($this->allocation->patient)->first_name.' '.optional($this->allocation->patient)->last_name,
                'admitted_at' => $this->allocation->admitted_at?->toISOString(),
            ];
        }

        return $payload;
    }
}
