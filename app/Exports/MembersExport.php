<?php
// filepath: app/Exports/MembersExport.php

namespace App\Exports;

use App\Models\Member;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MembersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    protected array $filters;
    protected array $selectedFields;
    protected array $includeOptions;

    public function __construct(array $filters = [], array $selectedFields = [], array $includeOptions = [])
    {
        $this->filters = $filters;
        $this->selectedFields = $selectedFields;
        $this->includeOptions = $includeOptions;
    }

    public function query()
    {
        $query = Member::query();

        // Apply filters using model scopes
        $query->search($this->filters['search'] ?? null)
              ->byChurch($this->filters['local_church'] ?? null)
              ->byGroup($this->filters['church_group'] ?? null)
              ->byStatus($this->filters['membership_status'] ?? null)
              ->byGender($this->filters['gender'] ?? null)
              ->byAgeGroup($this->filters['age_group'] ?? null);

        // Apply date range filter
        $this->applyDateRangeFilter($query);

        // Apply sorting with validation
        $sortBy = $this->validateSortField($this->filters['sort_by'] ?? 'last_name');
        $sortDirection = $this->validateSortDirection($this->filters['sort_direction'] ?? 'asc');
        $query->orderBy($sortBy, $sortDirection);

        // Apply limit
        if (!empty($this->filters['limit']) && is_numeric($this->filters['limit'])) {
            $query->limit((int) $this->filters['limit']);
        }

        return $query;
    }

    public function headings(): array
    {
        if (empty($this->selectedFields)) {
            return [
                'ID',
                'First Name',
                'Middle Name',
                'Last Name',
                'Date of Birth',
                'Age',
                'Gender',
                'ID Number',
                'Phone',
                'Email',
                'Residence',
                'Emergency Contact',
                'Emergency Phone',
                'Local Church',
                'Church Group',
                'Membership Status',
                'Membership Date',
                'Baptism Date',
                'Confirmation Date',
                'Matrimony Status',
                'Occupation',
                'Education Level',
                'Family Name',
                'Family Head',
                'Tribe',
                'Clan',
                'Notes',
                'Created At',
                'Updated At'
            ];
        }

        // Return custom headings based on selected fields
        $fieldLabels = $this->getFieldLabels();
        $headings = [];
        foreach ($this->selectedFields as $field) {
            $headings[] = $fieldLabels[$field] ?? ucfirst(str_replace('_', ' ', $field));
        }
        return $headings;
    }

    public function map($member): array
    {
        if (empty($this->selectedFields)) {
            return [
                $member->id,
                $member->first_name,
                $member->middle_name,
                $member->last_name,
                $member->date_of_birth?->format($this->getDateFormat()),
                $member->age,
                $member->gender,
                $member->id_number,
                $member->phone,
                $member->email,
                $member->residence,
                $member->emergency_contact,
                $member->emergency_phone,
                $member->local_church,
                $member->church_group,
                $member->membership_status,
                $member->membership_date?->format($this->getDateFormat()),
                $member->baptism_date?->format($this->getDateFormat()),
                $member->confirmation_date?->format($this->getDateFormat()),
                $member->matrimony_status,
                $member->occupation,
                $member->education_level,
                $member->family_name,
                $member->family_head,
                $member->tribe,
                $member->clan,
                $member->notes,
                $member->created_at?->format($this->getDateFormat()),
                $member->updated_at?->format($this->getDateFormat()),
            ];
        }

        // Map custom fields
        $data = [];
        foreach ($this->selectedFields as $field) {
            $data[] = $this->getFieldValue($member, $field);
        }
        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A:Z' => ['alignment' => ['wrapText' => true]],
        ];
    }

    public function title(): string
    {
        return 'Members Export - ' . now()->format('Y-m-d');
    }

    private function validateSortField(string $field): string
    {
        $validFields = [
            'id', 'first_name', 'middle_name', 'last_name', 'date_of_birth',
            'gender', 'phone', 'email', 'local_church', 'church_group',
            'membership_status', 'membership_date', 'created_at', 'updated_at'
        ];

        // Check if field contains function signature
        if (str_contains($field, 'function') || str_contains($field, '[native code]')) {
            return 'last_name';
        }

        return in_array($field, $validFields) ? $field : 'last_name';
    }

    private function validateSortDirection(string $direction): string
    {
        return in_array(strtolower($direction), ['asc', 'desc']) ? strtolower($direction) : 'asc';
    }

    private function getFieldValue($member, string $field)
    {
        return match($field) {
            'id' => $member->id,
            'first_name' => $member->first_name,
            'middle_name' => $member->middle_name,
            'last_name' => $member->last_name,
            'full_name' => $member->full_name,
            'date_of_birth' => $member->date_of_birth?->format($this->getDateFormat()),
            'age' => $member->age,
            'gender' => $member->gender,
            'id_number' => $member->id_number,
            'phone' => $member->phone,
            'email' => $member->email,
            'residence' => $member->residence,
            'emergency_contact' => $member->emergency_contact,
            'emergency_phone' => $member->emergency_phone,
            'local_church' => $member->local_church,
            'church_group' => $member->church_group,
            'membership_status' => $member->membership_status,
            'membership_date' => $member->membership_date?->format($this->getDateFormat()),
            'baptism_date' => $member->baptism_date?->format($this->getDateFormat()),
            'confirmation_date' => $member->confirmation_date?->format($this->getDateFormat()),
            'matrimony_status' => $member->matrimony_status,
            'occupation' => $member->occupation,
            'education_level' => $member->education_level,
            'family_name' => $member->family_name,
            'family_head' => $member->family_head,
            'tribe' => $member->tribe,
            'clan' => $member->clan,
            'notes' => $member->notes,
            'created_at' => $member->created_at?->format($this->getDateFormat()),
            'updated_at' => $member->updated_at?->format($this->getDateFormat()),
            default => '',
        };
    }

    private function getFieldLabels(): array
    {
        return [
            'id' => 'ID',
            'first_name' => 'First Name',
            'middle_name' => 'Middle Name',
            'last_name' => 'Last Name',
            'full_name' => 'Full Name',
            'date_of_birth' => 'Date of Birth',
            'age' => 'Age',
            'gender' => 'Gender',
            'id_number' => 'ID Number',
            'phone' => 'Phone',
            'email' => 'Email',
            'residence' => 'Residence',
            'emergency_contact' => 'Emergency Contact',
            'emergency_phone' => 'Emergency Phone',
            'local_church' => 'Local Church',
            'church_group' => 'Church Group',
            'membership_status' => 'Membership Status',
            'membership_date' => 'Membership Date',
            'baptism_date' => 'Baptism Date',
            'confirmation_date' => 'Confirmation Date',
            'matrimony_status' => 'Matrimony Status',
            'occupation' => 'Occupation',
            'education_level' => 'Education Level',
            'family_name' => 'Family Name',
            'family_head' => 'Family Head',
            'tribe' => 'Tribe',
            'clan' => 'Clan',
            'notes' => 'Notes',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    private function applyDateRangeFilter($query): void
    {
        $dateRange = $this->filters['date_range'] ?? 'all';
        
        if ($dateRange === 'all') {
            return;
        }

        $now = now();
        
        switch ($dateRange) {
            case 'this_year':
                $query->whereYear('created_at', $now->year);
                break;
            case 'last_year':
                $query->whereYear('created_at', $now->subYear()->year);
                break;
            case 'last_6_months':
                $query->where('created_at', '>=', $now->subMonths(6));
                break;
            case 'last_30_days':
                $query->where('created_at', '>=', $now->subDays(30));
                break;
            case 'custom':
                if (!empty($this->filters['start_date'])) {
                    $query->whereDate('created_at', '>=', $this->filters['start_date']);
                }
                if (!empty($this->filters['end_date'])) {
                    $query->whereDate('created_at', '<=', $this->filters['end_date']);
                }
                break;
        }
    }

    private function getDateFormat(): string
    {
        return match($this->filters['date_format'] ?? 'Y-m-d') {
            'd/m/Y' => 'd/m/Y',
            'm/d/Y' => 'm/d/Y',
            default => 'Y-m-d',
        };
    }
}