<?php
// filepath: app/Exports/MembersExport.php

namespace App\Exports;

use App\Models\Member;
use App\Helpers\DatabaseHelper;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MembersExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle, WithColumnFormatting
{
    protected array $filters;
    protected array $selectedFields;
    protected array $includeOptions;
    protected $membersCollection;

    public function __construct(array $filters = [], array $selectedFields = [], array $includeOptions = [], $membersCollection = null)
    {
        $this->filters = $filters;
        $this->selectedFields = $selectedFields;
        $this->includeOptions = $includeOptions;
        $this->membersCollection = $membersCollection;
    }

    public function collection()
    {
        // Use collection if provided, otherwise use query
        if ($this->membersCollection !== null) {
            if (is_array($this->membersCollection)) {
                return collect($this->membersCollection);
            }
            if ($this->membersCollection instanceof Collection) {
                return $this->membersCollection;
            }
            if (is_object($this->membersCollection) && method_exists($this->membersCollection, 'toArray')) {
                return collect($this->membersCollection->toArray());
            }
            if (is_object($this->membersCollection) && method_exists($this->membersCollection, 'all')) {
                return collect($this->membersCollection->all());
            }
            return collect($this->membersCollection);
        }
        
        // Use comprehensive query to fetch actual database data
        $query = $this->buildComprehensiveQuery();
        
        // Log query for debugging
        Log::info('MembersExport query executed', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'filters' => $this->filters
        ]);
        
        return $query->get();
    }

    private function buildComprehensiveQuery()
    {
        $query = Member::query()
            ->select([
                'id', 'first_name', 'middle_name', 'last_name', 'date_of_birth',
                'gender', 'phone', 'email', 'residence', 'local_church', 'church_group',
                'small_christian_community', 'membership_status', 'membership_date',
                'baptism_date', 'confirmation_date', 'matrimony_status', 'marriage_type',
                'occupation', 'education_level', 'tribe', 'clan', 'id_number',
                'created_at', 'updated_at'
            ]);

        // Apply comprehensive filters
        if (!empty($this->filters['search'])) {
            $search = trim($this->filters['search']);
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('middle_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('id_number', 'LIKE', "%{$search}%")
                  ->orWhereRaw('CONCAT(first_name, " ", COALESCE(middle_name, ""), " ", last_name) LIKE ?', ["%{$search}%"]);
            });
        }

        // Church and community filters
        if (!empty($this->filters['local_church'])) {
            $query->where('local_church', $this->filters['local_church']);
        }

        if (!empty($this->filters['church_group'])) {
            $query->where('church_group', $this->filters['church_group']);
        }

        if (!empty($this->filters['small_christian_community'])) {
            $query->where('small_christian_community', $this->filters['small_christian_community']);
        }

        // Personal information filters
        if (!empty($this->filters['membership_status'])) {
            $query->where('membership_status', $this->filters['membership_status']);
        }

        if (!empty($this->filters['gender'])) {
            $query->where('gender', $this->filters['gender']);
        }

        if (!empty($this->filters['education_level'])) {
            $query->where('education_level', $this->filters['education_level']);
        }

        if (!empty($this->filters['occupation'])) {
            $query->where('occupation', 'LIKE', "%{$this->filters['occupation']}%");
        }

        if (!empty($this->filters['tribe'])) {
            $query->where('tribe', $this->filters['tribe']);
        }

        if (!empty($this->filters['matrimony_status'])) {
            $query->where('matrimony_status', $this->filters['matrimony_status']);
        }

        if (!empty($this->filters['marriage_type'])) {
            $query->where('marriage_type', $this->filters['marriage_type']);
        }

        // Age filters
        if (!empty($this->filters['age_min'])) {
            $query->whereRaw(DatabaseHelper::getAgeSQL() . ' >= ?', [(int)$this->filters['age_min']]);
        }

        if (!empty($this->filters['age_max'])) {
            $query->whereRaw(DatabaseHelper::getAgeSQL() . ' <= ?', [(int)$this->filters['age_max']]);
        }

        if (!empty($this->filters['age_group'])) {
            $this->applyEnhancedAgeGroupFilter($query, $this->filters['age_group']);
        }

        // Sacrament filters
        if (isset($this->filters['has_baptism'])) {
            if ($this->filters['has_baptism'] === true || $this->filters['has_baptism'] === 'true') {
                $query->whereNotNull('baptism_date')->where('baptism_date', '!=', '');
            } elseif ($this->filters['has_baptism'] === false || $this->filters['has_baptism'] === 'false') {
                $query->where(function($q) {
                    $q->whereNull('baptism_date')->orWhere('baptism_date', '');
                });
            }
        }

        if (isset($this->filters['has_confirmation'])) {
            if ($this->filters['has_confirmation'] === true || $this->filters['has_confirmation'] === 'true') {
                $query->whereNotNull('confirmation_date')->where('confirmation_date', '!=', '');
            } elseif ($this->filters['has_confirmation'] === false || $this->filters['has_confirmation'] === 'false') {
                $query->where(function($q) {
                    $q->whereNull('confirmation_date')->orWhere('confirmation_date', '');
                });
            }
        }

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

    private function query()
    {
        return $this->buildComprehensiveQuery();
    }

    public function headings(): array
    {
        if (empty($this->selectedFields)) {
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
                'Membership Date',
                'Created At'
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
        // Handle both object and array data
        if (is_array($member)) {
            $member = (object) $member;
        }

        if (empty($this->selectedFields)) {
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
                $this->formatDate($member->created_at ?? null),
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
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'E3F2FD']],
                'borders' => ['allBorders' => ['borderStyle' => 'thin']]
            ],
            'A:AZ' => [
                'alignment' => ['wrapText' => true, 'vertical' => 'top'],
                'borders' => ['allBorders' => ['borderStyle' => 'thin']]
            ],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_DATE_DDMMYYYY, // Date of Birth
            'F' => NumberFormat::FORMAT_TEXT, // Phone - keep as text to preserve formatting
            'K' => NumberFormat::FORMAT_DATE_DDMMYYYY, // Membership Date
            'L' => NumberFormat::FORMAT_DATE_DATETIME, // Created At
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

    private function getFullName($member): string
    {
        $names = array_filter([
            $member->first_name ?? '',
            $member->middle_name ?? '',
            $member->last_name ?? ''
        ]);
        return implode(' ', $names);
    }

    private function calculateAge($dateOfBirth): string
    {
        if (!$dateOfBirth) return '';
        
        try {
            $birth = new \DateTime($dateOfBirth);
            $now = new \DateTime();
            return $birth->diff($now)->y . ' years';
        } catch (\Exception $e) {
            return '';
        }
    }

    private function formatDate($date): string
    {
        if (!$date) return '';
        
        try {
            if (is_string($date)) {
                $date = new \DateTime($date);
            }
            return $date->format($this->getDateFormat());
        } catch (\Exception $e) {
            return (string) $date;
        }
    }

    private function applyEnhancedAgeGroupFilter($query, $ageGroup): void
    {
        switch ($ageGroup) {
            case 'children':
            case '0-12':
                $query->whereRaw(DatabaseHelper::getAgeSQL() . ' BETWEEN 0 AND 12');
                break;
            case 'youth':
            case '13-24':
                $query->whereRaw(DatabaseHelper::getAgeSQL() . ' BETWEEN 13 AND 24');
                break;
            case 'young_adults':
            case '18-30':
                $query->whereRaw(DatabaseHelper::getAgeSQL() . ' BETWEEN 18 AND 30');
                break;
            case 'adults':
            case '25-59':
            case '31-50':
                $query->whereRaw(DatabaseHelper::getAgeSQL() . ' BETWEEN 25 AND 59');
                break;
            case 'middle_aged':
            case '51-70':
                $query->whereRaw(DatabaseHelper::getAgeSQL() . ' BETWEEN 51 AND 70');
                break;
            case 'seniors':
            case '60+':
            case '70+':
                $query->whereRaw(DatabaseHelper::getAgeSQL() . ' >= 60');
                break;
            case 'elderly':
                $query->whereRaw(DatabaseHelper::getAgeSQL() . ' >= 70');
                break;
            default:
                // Handle custom age ranges like "25-40"
                if (preg_match('/^(\\d+)-(\\d+)$/', $ageGroup, $matches)) {
                    $minAge = (int)$matches[1];
                    $maxAge = (int)$matches[2];
                    $query->whereRaw(DatabaseHelper::getAgeSQL() . ' BETWEEN ? AND ?', [$minAge, $maxAge]);
                } elseif (preg_match('/^(\\d+)\\+$/', $ageGroup, $matches)) {
                    $minAge = (int)$matches[1];
                    $query->whereRaw(DatabaseHelper::getAgeSQL() . ' >= ?', [$minAge]);
                }
                break;
        }
    }

    private function applyAgeGroupFilter($query, $ageGroup): void
    {
        $this->applyEnhancedAgeGroupFilter($query, $ageGroup);
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
            'id' => $member->id ?? '',
            'first_name' => $member->first_name ?? '',
            'middle_name' => $member->middle_name ?? '',
            'last_name' => $member->last_name ?? '',
            'full_name' => $this->getFullName($member),
            'date_of_birth' => $this->formatDate($member->date_of_birth ?? null),
            'age' => $this->calculateAge($member->date_of_birth ?? null),
            'gender' => $member->gender ?? '',
            'id_number' => $member->id_number ?? '',
            'phone' => $member->phone ?? '',
            'email' => $member->email ?? '',
            'residence' => $member->residence ?? '',
            'is_differently_abled' => $member->is_differently_abled ? 'Yes' : 'No',
            'disability_description' => $member->disability_description ?? '',
            'local_church' => $member->local_church ?? '',
            'church_group' => $member->church_group ?? '',
            'membership_status' => $member->membership_status ?? '',
            'membership_date' => $this->formatDate($member->membership_date ?? null),
            'baptism_date' => $this->formatDate($member->baptism_date ?? null),
            'confirmation_date' => $this->formatDate($member->confirmation_date ?? null),
            'matrimony_status' => $member->matrimony_status ?? '',
            'marriage_type' => $member->marriage_type ?? '',
            'occupation' => $member->occupation ?? '',
            'education_level' => $member->education_level ?? '',
            'family_name' => $member->family_name ?? '',
            'family_head' => $member->family_head ?? '',
            'tribe' => $member->tribe ?? '',
            'clan' => $member->clan ?? '',
            'small_christian_community' => $member->small_christian_community ?? '',
            'notes' => $member->notes ?? '',
            'created_at' => $this->formatDate($member->created_at ?? null),
            'updated_at' => $this->formatDate($member->updated_at ?? null),
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
            'is_differently_abled' => 'Differently Abled',
            'disability_description' => 'Disability Description',
            'local_church' => 'Local Church',
            'church_group' => 'Church Group',
            'membership_status' => 'Membership Status',
            'membership_date' => 'Membership Date',
            'baptism_date' => 'Baptism Date',
            'confirmation_date' => 'Confirmation Date',
            'matrimony_status' => 'Matrimony Status',
            'marriage_type' => 'Marriage Type',
            'occupation' => 'Occupation',
            'education_level' => 'Education Level',
            'family_name' => 'Family Name',
            'family_head' => 'Family Head',
            'tribe' => 'Tribe',
            'clan' => 'Clan',
            'small_christian_community' => 'Small Christian Community',
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