<?php

namespace App\Imports;

use App\Models\Attendance as AttendanceRecord;
use App\Models\AttendanceImportError;
use App\Models\Personnel;
use App\Models\WorkSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class AttendanceImport implements ToCollection, WithHeadingRow
{
    private int $totalRows = 0;

    private int $totalMatched = 0;

    private int $totalUnmatched = 0;

    public function __construct(
        private readonly int $importId,
        private readonly int $month,
        private readonly int $year,
    ) {}

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'file' => 'File harus berisi header dan setidaknya satu baris absensi.',
            ]);
        }

        $mappedRows = $rows->map(fn (Collection $row) => $this->mapHeadings($row));
        $requiredHeaders = ['nik', 'tanggal', 'jam_masuk', 'jam_pulang', 'status_kehadiran'];
        $missingHeaders = array_diff($requiredHeaders, $mappedRows->first()->keys()->all());
        if ($missingHeaders !== []) {
            throw ValidationException::withMessages([
                'file' => 'Kolom wajib tidak ditemukan: '.implode(', ', $missingHeaders).'. Unduh dan gunakan template impor.',
            ]);
        }

        foreach ($mappedRows as $index => $row) {
            if ($row->filter(fn ($value) => $value !== null && trim((string) $value) !== '')->isEmpty()) {
                continue;
            }

            $this->totalRows++;
            $rowNumber = $index + 2;
            $nik = trim((string) ($row['nik'] ?? ''));
            $name = trim((string) ($row['nama_petugas'] ?? ''));
            $personnel = $nik === '' ? null : Personnel::query()->where('nik', $nik)->first();
            $error = null;
            $date = null;
            $checkIn = null;
            $checkOut = null;
            $status = trim((string) ($row['status_kehadiran'] ?? ''));

            if (! $personnel) {
                $error = 'NIK tidak ditemukan pada master personel.';
            } else {
                try {
                    $date = $this->parseDate($row['tanggal'] ?? null);
                    if ((int) $date->format('n') !== $this->month || (int) $date->format('Y') !== $this->year) {
                        $error = 'Tanggal absensi berada di luar periode impor yang dipilih.';
                    } elseif (! in_array($status, AttendanceRecord::STATUSES, true)) {
                        $error = 'Status absensi tidak valid.';
                    } else {
                        $checkIn = $this->parseTime($row['jam_masuk'] ?? null);
                        $checkOut = $this->parseTime($row['jam_pulang'] ?? null);
                    }
                } catch (\InvalidArgumentException|\PhpOffice\PhpSpreadsheet\Exception $exception) {
                    $error = 'Tanggal atau jam tidak valid: '.$exception->getMessage();
                }
            }

            if ($error !== null) {
                AttendanceImportError::create([
                    'import_id' => $this->importId,
                    'raw_nik' => $nik,
                    'raw_name' => $name,
                    'row_number' => $rowNumber,
                    'error_message' => $error,
                ]);
                $this->totalUnmatched++;

                continue;
            }

            [$lateMinutes, $earlyLeaveMinutes] = $this->calculateScheduleDifferences(
                $personnel->id,
                $date,
                $checkIn,
                $checkOut,
            );

            AttendanceRecord::create([
                'import_id' => $this->importId,
                'personnel_id' => $personnel->id,
                'date' => $date->toDateString(),
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'early_leave_minutes' => $earlyLeaveMinutes,
            ]);
            $this->totalMatched++;
        }
    }

    public function totalRows(): int
    {
        return $this->totalRows;
    }

    public function totalMatched(): int
    {
        return $this->totalMatched;
    }

    public function totalUnmatched(): int
    {
        return $this->totalUnmatched;
    }

    /**
     * @return Collection<string, mixed>
     */
    private function mapHeadings(Collection $row): Collection
    {
        $mapped = collect();
        foreach ($row as $heading => $value) {
            $canonicalHeading = match ((string) $heading) {
                'nik' => 'nik',
                'nama_petugas', 'nama', 'name' => 'nama_petugas',
                'tanggal', 'tanggal_yyyy_mm_dd', 'date' => 'tanggal',
                'jam_masuk', 'jam_masuk_hh_mm', 'jam_masuk_hhmm', 'check_in', 'check_in_hh_mm' => 'jam_masuk',
                'jam_pulang', 'jam_pulang_hh_mm', 'jam_pulang_hhmm', 'check_out', 'check_out_hh_mm' => 'jam_pulang',
                'status', 'status_kehadiran' => 'status_kehadiran',
                default => null,
            };

            if ($canonicalHeading !== null) {
                $mapped->put($canonicalHeading, $value);
            }
        }

        return $mapped;
    }

    private function parseDate(mixed $value): CarbonImmutable
    {
        if ($value === null || trim((string) $value) === '') {
            throw new \InvalidArgumentException('tanggal kosong');
        }

        if (is_numeric($value)) {
            return CarbonImmutable::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfDay();
        }

        return CarbonImmutable::parse(trim((string) $value))->startOfDay();
    }

    private function parseTime(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('H:i:s');
        }

        return CarbonImmutable::parse(trim((string) $value))->format('H:i:s');
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function calculateScheduleDifferences(int $personnelId, CarbonImmutable $date, ?string $checkIn, ?string $checkOut): array
    {
        if ($checkIn === null && $checkOut === null) {
            return [0, 0];
        }

        $schedule = WorkSchedule::query()
            ->with('shift')
            ->where('personnel_id', $personnelId)
            ->whereDate('date', $date->toDateString())
            ->first();
        if (! $schedule) {
            return [0, 0];
        }

        $start = CarbonImmutable::parse($date->toDateString().' '.$schedule->shift->start_time);
        $end = CarbonImmutable::parse($date->toDateString().' '.$schedule->shift->end_time);
        if ($end->lessThanOrEqualTo($start)) {
            $end = $end->addDay();
        }

        $late = 0;
        if ($checkIn !== null) {
            $actualIn = CarbonImmutable::parse($date->toDateString().' '.$checkIn);
            $late = $actualIn->greaterThan($start) ? $start->diffInMinutes($actualIn) : 0;
        }

        $early = 0;
        if ($checkOut !== null) {
            $actualOut = CarbonImmutable::parse($date->toDateString().' '.$checkOut);
            if ($end->day !== $date->day && $actualOut->lessThan($start)) {
                $actualOut = $actualOut->addDay();
            }
            $early = $actualOut->lessThan($end) ? $actualOut->diffInMinutes($end) : 0;
        }

        return [(int) $late, (int) $early];
    }
}
