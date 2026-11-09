<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationAndPersonnelSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $admin = User::where('email', 'admin@cleaningservice.local')->firstOrFail();
            $building = Building::updateOrCreate(
                ['code' => 'GDU-PST'],
                ['name' => 'Gedung Pusat', 'description' => 'Gedung utama operasional', 'is_active' => true],
            );

            $floors = collect(range(1, 3))->mapWithKeys(function (int $number) use ($building): array {
                $floor = Floor::updateOrCreate(
                    ['building_id' => $building->id, 'floor_number' => $number],
                    ['name' => 'Lantai '.$number, 'is_active' => true],
                );

                foreach ([
                    ['Toilet Pria', 'toilet'],
                    ['Lobby Utama', 'public'],
                    ['Koridor Timur', 'corridor'],
                    ['Ruang Rapat', 'office'],
                ] as [$name, $type]) {
                    Area::updateOrCreate(
                        ['floor_id' => $floor->id, 'name' => $name],
                        ['type' => $type, 'is_active' => true],
                    );
                }

                return [$number => $floor];
            });

            $positions = collect([
                ['code' => 'CLN', 'name' => 'Cleaner', 'description' => 'Petugas kebersihan operasional'],
                ['code' => 'TL', 'name' => 'Team Leader', 'description' => 'Koordinator tim cleaning service'],
                ['code' => 'WST', 'name' => 'Waste Specialist', 'description' => 'Petugas pengelolaan limbah'],
            ])->mapWithKeys(function (array $position): array {
                $model = Position::updateOrCreate(
                    ['code' => $position['code']],
                    ['name' => $position['name'], 'description' => $position['description']],
                );

                return [$position['code'] => $model];
            });

            $assignments = [
                ['name' => 'Petugas Cleaning 01', 'position' => 'CLN', 'floor' => 1, 'gender' => 'L'],
                ['name' => 'Petugas Cleaning 02', 'position' => 'CLN', 'floor' => 2, 'gender' => 'P'],
                ['name' => 'Petugas Cleaning 03', 'position' => 'CLN', 'floor' => 3, 'gender' => 'L'],
                ['name' => 'Petugas Cleaning 04', 'position' => 'TL', 'floor' => 1, 'gender' => 'P'],
                ['name' => 'Petugas Cleaning 05', 'position' => 'WST', 'floor' => 2, 'gender' => 'L'],
            ];

            foreach ($assignments as $assignment) {
                $personnel = Personnel::withTrashed()->firstOrNew(['name' => $assignment['name']]);
                if (! $personnel->exists) {
                    $personnel->nik = (string) random_int(1000000000000000, 9999999999999999);
                }

                $personnel->fill([
                    'gender' => $assignment['gender'],
                    'position_id' => $positions[$assignment['position']]->id,
                    'floor_id' => $floors[$assignment['floor']]->id,
                    'status' => 'Aktif',
                    'join_date' => now()->subDays(random_int(30, 120))->toDateString(),
                ])->setAttribute('nik', $personnel->nik)->save();

                if ($personnel->trashed()) {
                    $personnel->restore();
                }

                $hasInitialStatus = $personnel->statusHistories()->exists();
                if (! $hasInitialStatus) {
                    $personnel->statusHistories()->create([
                        'old_status' => '',
                        'new_status' => 'Aktif',
                        'reason' => 'Data awal seeder',
                        'changed_by' => $admin->id,
                    ]);
                }

                $hasInitialMutation = $personnel->mutationHistories()->exists();
                if (! $hasInitialMutation) {
                    $personnel->mutationHistories()->create([
                        'from_floor_id' => null,
                        'to_floor_id' => $floors[$assignment['floor']]->id,
                        'reason' => 'Penempatan awal seeder',
                        'mutated_by' => $admin->id,
                        'effective_date' => $personnel->join_date,
                    ]);
                }
            }
        });
    }
}
