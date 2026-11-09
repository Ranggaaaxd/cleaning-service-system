<?php

namespace Database\Seeders;

use App\Models\Personnel;
use App\Models\Shift;
use App\Models\WfhSchedule;
use App\Models\WorkSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShiftAndScheduleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $shifts = collect([
                ['name' => 'Pagi', 'code' => 'P', 'start_time' => '06:00:00', 'end_time' => '15:00:00', 'color_badge' => 'blue'],
                ['name' => 'Middle', 'code' => 'M', 'start_time' => '10:00:00', 'end_time' => '19:00:00', 'color_badge' => 'amber'],
                ['name' => 'Siang', 'code' => 'S', 'start_time' => '13:00:00', 'end_time' => '22:00:00', 'color_badge' => 'emerald'],
            ])->mapWithKeys(function (array $data): array {
                $shift = Shift::updateOrCreate(['code' => $data['code']], $data + ['is_active' => true]);

                return [$data['code'] => $shift];
            });

            $personnels = Personnel::query()->where('status', 'Aktif')->orderBy('id')->get();
            $start = CarbonImmutable::today();
            foreach ($personnels as $index => $personnel) {
                if ($personnel->floor_id === null) {
                    continue;
                }

                for ($day = 0; $day < 7; $day++) {
                    $date = $start->addDays($day);
                    if (WfhSchedule::query()
                        ->where('personnel_id', $personnel->id)
                        ->whereDate('date', $date->toDateString())
                        ->exists()) {
                        continue;
                    }

                    $shiftCode = ['P', 'M', 'S'][($index + $day) % 3];
                    $schedule = WorkSchedule::query()
                        ->where('personnel_id', $personnel->id)
                        ->whereDate('date', $date->toDateString())
                        ->first();

                    if ($schedule) {
                        $schedule->update(['shift_id' => $shifts[$shiftCode]->id, 'floor_id' => $personnel->floor_id]);
                    } else {
                        WorkSchedule::create([
                            'personnel_id' => $personnel->id,
                            'date' => $date->toDateString(),
                            'shift_id' => $shifts[$shiftCode]->id,
                            'floor_id' => $personnel->floor_id,
                        ]);
                    }
                }
            }
        });
    }
}
