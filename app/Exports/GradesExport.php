<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GradesExport implements FromCollection, WithMapping, WithHeadings
{
    protected $grades;
    protected $isPersonality;

    public function __construct($grades, $isPersonality = false)
    {
        $this->grades = $grades;
        $this->isPersonality = $isPersonality;
    }

    public function collection()
    {
        return $this->grades;
    }

    protected function formatViolationDescription($grade): string
    {
        // Coba ambil dari exam_group atau examGroup
        $examGroup = $grade->exam_group ?? $grade->examGroup ?? null;
        
        $violations = $examGroup && isset($examGroup->exam_violations)
            ? $examGroup->exam_violations
            : [];

        if (empty($violations)) {
            return 'Tidak ada pelanggaran';
        }

        if (is_array($violations)) {
            $items = $violations;
        } elseif (method_exists($violations, 'map')) {
            $items = $violations->all();
        } else {
            $items = [$violations];
        }

        return collect($items)
            ->map(function ($violation) {
                $type = is_object($violation) ? ($violation->violation_type ?? '') : ($violation['violation_type'] ?? '');
                $time = is_object($violation) ? ($violation->violation_time ?? null) : ($violation['violation_time'] ?? null);

                $label = match ($type) {
                    'tab_switch' => 'Berpindah Tab',
                    'exit_fullscreen' => 'Keluar Fullscreen',
                    'isolated_by_admin' => 'Isolasi Admin',
                    default => ucfirst((string) $type ?: 'Unknown'),
                };

                $formattedTime = $time ? \Carbon\Carbon::parse($time)->format('d-m-Y H:i:s') : '-';

                return $label . ' (' . $formattedTime . ')';
            })
            ->implode(' | ');
    }

    public function map($grade): array
    {
        $lessonName = $grade->exam && $grade->exam->lesson ? $grade->exam->lesson->name : '-';
        $examGroup = $grade->exam_group ?? $grade->examGroup ?? null;
        $violationCount = $examGroup ? ((int) ($examGroup->violation_count ?? 0)) : 0;
        $submissionDate = $grade->created_at ? $grade->created_at->format('d-m-Y H:i:s') : '-';
        $violationDetails = $this->formatViolationDescription($grade);

        if ($this->isPersonality) {
            return [
                $grade->exam->title,
                $grade->student->name,
                $submissionDate,
                $lessonName,
                $grade->total_points . ' / ' . $grade->max_points,
                $grade->grade . '%',
                $violationCount,
                $violationDetails,
                $grade->status === 'baik' ? 'Baik' : 'Kurang Baik',
            ];
        }

        return [
            $grade->exam->title,
            $grade->student->name,
            $submissionDate,
            $lessonName,
            $grade->grade,
            $violationCount,
            $violationDetails,
        ];
    }

    public function headings(): array
    {
        if ($this->isPersonality) {
            return [
                'Ujian',
                'Nama Siswa',
                'Tanggal Pengerjaan',
                'Mapel',
                'Point',
                'Persentase',
                'Jumlah Pelanggaran',
                'Detail Pelanggaran',
                'Status'
            ];
        }

        return [
            'Ujian',
            'Nama Siswa',
            'Tanggal Pengerjaan',
            'Mapel',
            'Nilai',
            'Jumlah Pelanggaran',
            'Detail Pelanggaran'
        ];
    }
}
