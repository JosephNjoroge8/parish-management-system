<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class OptimizedMembersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle, WithChunkReading, WithColumnFormatting
{
    protected $query;
    protected array $filters;

    public function __construct($query, array $filters = [])
    {
        $this->query = $query;
        $this->filters = $filters;
    }

    public function query()
    {
        return $this->query;
    }

    public function chunkSize(): int
    {
        return 1000; // Process 1000 records at a time
    }

    public function headings(): array
    {
        return [
            'ID',
            'First Name',
            'Last Name',
            'Date of Birth',
            'Gender',
            'Phone',
            'Email',
            'Local Church',
            'Church Group',
            'Membership Status',
            'Membership Date'
        ];
    }

    public function map($member): array
    {
        return [
            $member->id ?? '',
            $member->first_name ?? '',
            $member->last_name ?? '',
            $this->formatDate($member->date_of_birth ?? null),
            $member->gender ?? '',
            $this->formatPhone($member->phone ?? ''),
            $member->email ?? '',
            $member->local_church ?? '',
            $member->church_group ?? '',
            $member->membership_status ?? '',
            $this->formatDate($member->membership_date ?? null),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_DATE_DDMMYYYY, // Date of Birth
            'F' => NumberFormat::FORMAT_TEXT, // Phone - keep as text to preserve formatting
            'K' => NumberFormat::FORMAT_DATE_DDMMYYYY, // Membership Date
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'E3F2FD']],
                'borders' => ['allBorders' => ['borderStyle' => 'thin']]
            ]
        ];
    }

    public function title(): string
    {
        $title = 'Members Export';
        
        if (!empty($this->filters['local_church'])) {
            $title .= ' - ' . $this->filters['local_church'];
        }
        
        if (!empty($this->filters['church_group'])) {
            $title .= ' - ' . $this->filters['church_group'];
        }
        
        $title .= ' - ' . now()->format('Y-m-d H:i');
        
        return $title;
    }

    private function formatDate($date): string
    {
        if (!$date) return '';
        
        try {
            if (is_string($date)) {
                $date = new \DateTime($date);
            }
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            return (string) $date;
        }
    }

    private function formatPhone($phone): string
    {
        if (!$phone) return '';
        
        // Ensure phone number is treated as text by prefixing with single quote
        // This prevents Excel from auto-formatting phone numbers
        $phone = trim($phone);
        
        // If phone starts with + or contains special characters, ensure it's preserved
        if (preg_match('/^[\+\-\(\)\s\d]+$/', $phone)) {
            return "'" . $phone; // Prefix with single quote to force text format
        }
        
        return $phone;
    }
}
