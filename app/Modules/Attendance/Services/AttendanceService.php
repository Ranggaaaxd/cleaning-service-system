<?php

namespace App\Modules\Attendance\Services;

use App\Imports\AttendanceImport as AttendanceExcelImport;
use App\Models\AttendanceImport as AttendanceImportRecord;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function importFromExcel(UploadedFile $file, int $month, int $year, int $userId): AttendanceImportRecord
    {
        if (! checkdate($month, 1, $year)) {
            throw ValidationException::withMessages(['period' => 'Periode bulan dan tahun tidak valid.']);
        }

        return DB::transaction(function () use ($file, $month, $year, $userId): AttendanceImportRecord {
            $periodVersions = AttendanceImportRecord::query()
                    ->where('period_month', $month)
                    ->where('period_year', $year)
                    ->lockForUpdate()
                    ->get();
            $version = ((int) $periodVersions->max('version')) + 1;
            AttendanceImportRecord::query()
                ->whereIn('id', $periodVersions->pluck('id'))
                ->update(['is_active' => false]);

            $import = AttendanceImportRecord::create([
                'file_name' => basename($file->getClientOriginalName()),
                'imported_by' => $userId,
                'period_month' => $month,
                'period_year' => $year,
                'version' => $version,
                'is_active' => true,
            ]);
            $excelImport = new AttendanceExcelImport($import->id, $month, $year);
            try {
                \Maatwebsite\Excel\Facades\Excel::import($excelImport, $file);
            } catch (\Maatwebsite\Excel\Validators\ValidationException $exception) {
                throw ValidationException::withMessages([
                    'file' => 'File absensi tidak valid: '.$exception->getMessage(),
                ]);
            }

            $import->update([
                'total_rows' => $excelImport->totalRows(),
                'total_matched' => $excelImport->totalMatched(),
                'total_unmatched' => $excelImport->totalUnmatched(),
            ]);

            AuditService::log(
                User::findOrFail($userId),
                'attendance',
                'import',
                $import,
                newValues: [
                    'period_month' => $month,
                    'period_year' => $year,
                    'version' => $version,
                    'total_rows' => $excelImport->totalRows(),
                    'total_matched' => $excelImport->totalMatched(),
                    'total_unmatched' => $excelImport->totalUnmatched(),
                ],
            );

            return $import;
        });
    }
}
