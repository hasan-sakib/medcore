<?php

namespace Database\Seeders;

use App\Models\Bed;
use App\Models\OperatingRoom;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\Ward;
use App\Support\TenantManager;
use Illuminate\Database\Seeder;

class BedSeeder extends Seeder
{
    public function run(): void
    {
        $manager = app(TenantManager::class);
        $tenants = Tenant::where('status', 'active')->get();

        foreach ($tenants as $tenant) {
            $manager->setCurrent($tenant);
            $this->seedForTenant($tenant->id);
            $manager->clearCurrent();
        }
    }

    private function seedForTenant(int $tenantId): void
    {
        $wards = [
            ['name' => 'General Ward A', 'code' => 'GWA', 'floor' => '1', 'ward_type' => 'general'],
            ['name' => 'ICU',             'code' => 'ICU', 'floor' => '2', 'ward_type' => 'icu'],
            ['name' => 'Pediatric Ward',  'code' => 'PED', 'floor' => '1', 'ward_type' => 'pediatric'],
        ];

        foreach ($wards as $wardData) {
            $ward = Ward::create(array_merge($wardData, ['is_active' => true]));

            $bedCount  = match ($wardData['ward_type']) { 'icu' => 6, default => 12 };
            $bedType   = $wardData['ward_type'] === 'icu' ? 'icu' : 'standard';
            $prefix    = $wardData['code'];

            // Rooms: 2 beds per room for general, 1 per room for ICU
            $bedsPerRoom = $wardData['ward_type'] === 'icu' ? 1 : 2;
            $roomCount   = intdiv($bedCount, $bedsPerRoom);
            $bedIndex    = 1;

            for ($r = 1; $r <= $roomCount; $r++) {
                $room = Room::create([
                    'ward_id'     => $ward->id,
                    'room_number' => $prefix.'-R'.str_pad((string) $r, 2, '0', STR_PAD_LEFT),
                    'room_type'   => $wardData['ward_type'] === 'icu' ? 'icu' : 'general',
                    'is_active'   => true,
                ]);

                for ($b = 0; $b < $bedsPerRoom; $b++, $bedIndex++) {
                    Bed::create([
                        'ward_id'    => $ward->id,
                        'room_id'    => $room->id,
                        'bed_number' => $prefix.'-'.str_pad((string) $bedIndex, 3, '0', STR_PAD_LEFT),
                        'bed_type'   => $bedType,
                        'status'     => 'available',
                        'is_active'  => true,
                    ]);
                }
            }
        }

        // Operating rooms
        $orRooms = [
            ['name' => 'Main Theatre 1', 'room_number' => 'OR-01', 'or_type' => 'general'],
            ['name' => 'Main Theatre 2', 'room_number' => 'OR-02', 'or_type' => 'general'],
            ['name' => 'Emergency OR',   'room_number' => 'OR-03', 'or_type' => 'emergency'],
        ];

        foreach ($orRooms as $orData) {
            OperatingRoom::create(array_merge($orData, ['status' => 'available', 'is_active' => true]));
        }
    }
}
