<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\Sacrament;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class ComprehensiveReportExport implements WithMultipleSheets
{
    protected $filters;
    
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        return [
            'Summary' => new SummarySheet($this->filters),
            'Members' => new MembersSheet($this->filters),
            'Sacraments' => new SacramentsSheet($this->filters),
            'Church Groups' => new ChurchGroupsSheet($this->filters),
            'Local Churches' => new LocalChurchesSheet($this->filters),
            'Small Communities' => new SmallCommunitiesSheet($this->filters),
            'Age Groups' => new AgeGroupsSheet($this->filters),
            'Education Levels' => new EducationLevelsSheet($this->filters),
            'Tribes' => new TribesSheet($this->filters),
        ];
    }
}

class SummarySheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    protected $filters;
    
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $members = Member::generateComprehensiveReport($this->filters);
        
        return collect([
            [
                'Total Members',
                $members->count(),
                ''
            ],
            [
                'Active Members',
                $members->where('membership_status', 'active')->count(),
                ''
            ],
            [
                'Inactive Members',
                $members->where('membership_status', 'inactive')->count(),
                ''
            ],
            [
                'Transferred Members',
                $members->where('membership_status', 'transferred')->count(),
                ''
            ],
            [
                'Deceased Members',
                $members->where('membership_status', 'deceased')->count(),
                ''
            ],
            ['', '', ''],
            [
                'Male Members',
                $members->where('gender', 'Male')->count(),
                ''
            ],
            [
                'Female Members',
                $members->where('gender', 'Female')->count(),
                ''
            ],
            ['', '', ''],
            [
                'Children (0-12)',
                $members->filter(function($m) { return $m->age && $m->age <= 12; })->count(),
                ''
            ],
            [
                'Youth (13-24)',
                $members->filter(function($m) { return $m->age && $m->age >= 13 && $m->age <= 24; })->count(),
                ''
            ],
            [
                'Adults (25-59)',
                $members->filter(function($m) { return $m->age && $m->age >= 25 && $m->age <= 59; })->count(),
                ''
            ],
            [
                'Seniors (60+)',
                $members->filter(function($m) { return $m->age && $m->age >= 60; })->count(),
                ''
            ],
            ['', '', ''],
            [
                'Baptisms This Year',
                Sacrament::where('sacrament_type', 'baptism')->whereYear('sacrament_date', date('Y'))->count(),
                ''
            ],
            [
                'Confirmations This Year',
                Sacrament::where('sacrament_type', 'confirmation')->whereYear('sacrament_date', date('Y'))->count(),
                ''
            ],
            [
                'Marriages This Year',
                Sacrament::where('sacrament_type', 'marriage')->whereYear('sacrament_date', date('Y'))->count(),
                ''
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'Metric',
            'Count',
            'Percentage'
        ];
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            'A:C' => ['alignment' => ['horizontal' => 'left']],
        ];
    }
}

class MembersSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    protected $filters;
    
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return Member::generateComprehensiveReport($this->filters);
    }

    public function map($member): array
    {
        return [
            $member->id,
            $member->full_name,
            $member->date_of_birth?->format('Y-m-d'),
            $member->age,
            $member->gender,
            $member->phone,
            $member->email,
            $member->local_church,
            $member->small_christian_community,
            $member->church_group,
            implode(', ', $member->all_church_groups),
            $member->membership_status,
            $member->membership_date?->format('Y-m-d'),
            $member->education_level_name,
            $member->occupation,
            $member->tribe,
            $member->clan,
            $member->baptism_date?->format('Y-m-d'),
            $member->confirmation_date?->format('Y-m-d'),
            $member->matrimony_status,
            $member->marriage_type_name,
            $member->residence,
            $member->emergency_contact,
            $member->emergency_phone,
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Full Name',
            'Date of Birth',
            'Age',
            'Gender',
            'Phone',
            'Email',
            'Local Church',
            'Small Christian Community',
            'Primary Church Group',
            'All Church Groups',
            'Membership Status',
            'Membership Date',
            'Education Level',
            'Occupation',
            'Tribe',
            'Clan',
            'Baptism Date',
            'Confirmation Date',
            'Matrimony Status',
            'Marriage Type',
            'Residence',
            'Emergency Contact',
            'Emergency Phone',
        ];
    }

    public function title(): string
    {
        return 'Members';
    }
}

class SacramentsSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    protected $filters;
    
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Sacrament::with('member');
        
        // Apply filters if needed
        if (isset($this->filters['sacrament_type'])) {
            $query->where('sacrament_type', $this->filters['sacrament_type']);
        }
        
        return $query->get();
    }

    public function map($sacrament): array
    {
        return [
            $sacrament->id,
            $sacrament->member->full_name ?? 'Unknown',
            $sacrament->sacrament_type_name,
            $sacrament->sacrament_date?->format('Y-m-d'),
            $sacrament->location,
            $sacrament->celebrant,
            $sacrament->witness_1,
            $sacrament->witness_2,
            $sacrament->godparent_1,
            $sacrament->godparent_2,
            $sacrament->certificate_number,
            $sacrament->book_number,
            $sacrament->page_number,
            $sacrament->notes,
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Member Name',
            'Sacrament Type',
            'Date',
            'Location',
            'Celebrant',
            'Witness 1',
            'Witness 2',
            'Godparent 1',
            'Godparent 2',
            'Certificate Number',
            'Book Number',
            'Page Number',
            'Notes',
        ];
    }

    public function title(): string
    {
        return 'Sacraments';
    }
}

class ChurchGroupsSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $filters;
    
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $members = Member::generateComprehensiveReport($this->filters);
        
        return $members->groupBy('church_group')->map(function ($group, $groupName) {
            return [
                'group_name' => $groupName,
                'total_members' => $group->count(),
                'active_members' => $group->where('membership_status', 'active')->count(),
                'inactive_members' => $group->where('membership_status', 'inactive')->count(),
                'male_members' => $group->where('gender', 'Male')->count(),
                'female_members' => $group->where('gender', 'Female')->count(),
            ];
        })->values();
    }

    public function headings(): array
    {
        return [
            'Church Group',
            'Total Members',
            'Active Members',
            'Inactive Members',
            'Male Members',
            'Female Members',
        ];
    }

    public function title(): string
    {
        return 'Church Groups';
    }
}

class LocalChurchesSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $filters;
    
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $members = Member::generateComprehensiveReport($this->filters);
        
        return $members->groupBy('local_church')->map(function ($church, $churchName) {
            return [
                'church_name' => $churchName,
                'total_members' => $church->count(),
                'active_members' => $church->where('membership_status', 'active')->count(),
                'inactive_members' => $church->where('membership_status', 'inactive')->count(),
                'male_members' => $church->where('gender', 'Male')->count(),
                'female_members' => $church->where('gender', 'Female')->count(),
            ];
        })->values();
    }

    public function headings(): array
    {
        return [
            'Local Church',
            'Total Members',
            'Active Members',
            'Inactive Members',
            'Male Members',
            'Female Members',
        ];
    }

    public function title(): string
    {
        return 'Local Churches';
    }
}

class SmallCommunitiesSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $filters;
    
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $members = Member::generateComprehensiveReport($this->filters);
        
        return $members->whereNotNull('small_christian_community')
            ->groupBy('small_christian_community')
            ->map(function ($community, $communityName) {
                return [
                    'community_name' => $communityName,
                    'total_members' => $community->count(),
                    'active_members' => $community->where('membership_status', 'active')->count(),
                    'inactive_members' => $community->where('membership_status', 'inactive')->count(),
                    'male_members' => $community->where('gender', 'Male')->count(),
                    'female_members' => $community->where('gender', 'Female')->count(),
                ];
            })->values();
    }

    public function headings(): array
    {
        return [
            'Small Christian Community',
            'Total Members',
            'Active Members',
            'Inactive Members',
            'Male Members',
            'Female Members',
        ];
    }

    public function title(): string
    {
        return 'Small Communities';
    }
}

class AgeGroupsSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $filters;
    
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $members = Member::generateComprehensiveReport($this->filters);
        
        return collect([
            [
                'age_group' => 'Children (0-12)',
                'total_members' => $members->filter(function($m) { return $m->age && $m->age <= 12; })->count(),
                'male_members' => $members->filter(function($m) { return $m->age && $m->age <= 12 && $m->gender === 'Male'; })->count(),
                'female_members' => $members->filter(function($m) { return $m->age && $m->age <= 12 && $m->gender === 'Female'; })->count(),
            ],
            [
                'age_group' => 'Youth (13-24)',
                'total_members' => $members->filter(function($m) { return $m->age && $m->age >= 13 && $m->age <= 24; })->count(),
                'male_members' => $members->filter(function($m) { return $m->age && $m->age >= 13 && $m->age <= 24 && $m->gender === 'Male'; })->count(),
                'female_members' => $members->filter(function($m) { return $m->age && $m->age >= 13 && $m->age <= 24 && $m->gender === 'Female'; })->count(),
            ],
            [
                'age_group' => 'Adults (25-59)',
                'total_members' => $members->filter(function($m) { return $m->age && $m->age >= 25 && $m->age <= 59; })->count(),
                'male_members' => $members->filter(function($m) { return $m->age && $m->age >= 25 && $m->age <= 59 && $m->gender === 'Male'; })->count(),
                'female_members' => $members->filter(function($m) { return $m->age && $m->age >= 25 && $m->age <= 59 && $m->gender === 'Female'; })->count(),
            ],
            [
                'age_group' => 'Seniors (60+)',
                'total_members' => $members->filter(function($m) { return $m->age && $m->age >= 60; })->count(),
                'male_members' => $members->filter(function($m) { return $m->age && $m->age >= 60 && $m->gender === 'Male'; })->count(),
                'female_members' => $members->filter(function($m) { return $m->age && $m->age >= 60 && $m->gender === 'Female'; })->count(),
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'Age Group',
            'Total Members',
            'Male Members',
            'Female Members',
        ];
    }

    public function title(): string
    {
        return 'Age Groups';
    }
}

class EducationLevelsSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $filters;
    
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $members = Member::generateComprehensiveReport($this->filters);
        
        return $members->whereNotNull('education_level')
            ->groupBy('education_level')
            ->map(function ($group, $level) {
                return [
                    'education_level' => Member::EDUCATION_LEVELS[$level] ?? $level,
                    'total_members' => $group->count(),
                    'male_members' => $group->where('gender', 'Male')->count(),
                    'female_members' => $group->where('gender', 'Female')->count(),
                ];
            })->values();
    }

    public function headings(): array
    {
        return [
            'Education Level',
            'Total Members',
            'Male Members',
            'Female Members',
        ];
    }

    public function title(): string
    {
        return 'Education Levels';
    }
}

class TribesSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $filters;
    
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $members = Member::generateComprehensiveReport($this->filters);
        
        return $members->whereNotNull('tribe')
            ->groupBy('tribe')
            ->map(function ($group, $tribe) {
                return [
                    'tribe' => $tribe,
                    'total_members' => $group->count(),
                    'male_members' => $group->where('gender', 'Male')->count(),
                    'female_members' => $group->where('gender', 'Female')->count(),
                ];
            })->values()
            ->sortByDesc('total_members');
    }

    public function headings(): array
    {
        return [
            'Tribe',
            'Total Members',
            'Male Members',
            'Female Members',
        ];
    }

    public function title(): string
    {
        return 'Tribes';
    }
}
