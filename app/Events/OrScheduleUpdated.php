<?php

namespace App\Events;

use App\Models\OrSchedule;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrScheduleUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly OrSchedule $schedule) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.'.$this->schedule->tenant_id.'.or'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'or.schedule.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->schedule->id,
            'operating_room_id' => $this->schedule->operating_room_id,
            'procedure_name' => $this->schedule->procedure_name,
            'scheduled_start' => $this->schedule->scheduled_start->toISOString(),
            'scheduled_end' => $this->schedule->scheduled_end->toISOString(),
            'actual_start' => $this->schedule->actual_start?->toISOString(),
            'actual_end' => $this->schedule->actual_end?->toISOString(),
            'status' => $this->schedule->status,
            'surgeon' => [
                'id' => $this->schedule->surgeon_id,
                'name' => optional($this->schedule->surgeon)->name,
            ],
        ];
    }
}
