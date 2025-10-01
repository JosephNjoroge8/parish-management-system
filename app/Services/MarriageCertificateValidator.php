<?php

namespace App\Services;

use App\Models\Member;
use Illuminate\Support\Collection;

class MarriageCertificateValidator
{
    /**
     * Required fields for a complete marriage certificate
     */
    private array $requiredFields = [
        'basic_info' => [
            'marriage_date' => 'Marriage Date',
            'marriage_location' => 'Marriage Location'
        ],
        'husband_details' => [
            'husband_name' => 'Husband\'s Full Name',
            'husband_age' => 'Husband\'s Age'
        ],
        'wife_details' => [
            'wife_name' => 'Wife\'s Full Name', 
            'wife_age' => 'Wife\'s Age'
        ],
        'ceremony_details' => [
            'presence_of' => 'Officiant/Priest Name'
        ]
    ];

    /**
     * Highly recommended fields that improve certificate quality
     */
    private array $recommendedFields = [
        'husband_occupation' => 'Husband\'s Occupation',
        'husband_father_name' => 'Husband\'s Father\'s Name',
        'husband_mother_name' => 'Husband\'s Mother\'s Name',
        'wife_occupation' => 'Wife\'s Occupation',
        'wife_father_name' => 'Wife\'s Father\'s Name',
        'wife_mother_name' => 'Wife\'s Mother\'s Name',
        'male_witness_full_name' => 'Male Witness Name',
        'female_witness_full_name' => 'Female Witness Name',
        'religion' => 'Religion/Ceremony Type'
    ];

    /**
     * Optional fields that enhance the certificate but aren't critical
     */
    private array $optionalFields = [
        'civil_marriage_certificate_number',
        'banns_number', 
        'husband_county',
        'wife_county',
        'husband_father_occupation',
        'husband_mother_occupation',
        'wife_father_occupation',
        'wife_mother_occupation',
        'male_witness_father',
        'female_witness_father'
    ];

    /**
     * Validate member data for marriage certificate generation
     * 
     * @param Member $member
     * @return array
     */
    public function validateMemberData(Member $member): array
    {
        $validation = [
            'is_valid' => true,
            'completeness_score' => 0,
            'missing_required' => [],
            'missing_optional' => [],
            'field_mapping' => [],
            'warnings' => [],
            'recommendations' => []
        ];

        // Check basic marriage eligibility
        $hasMarriageData = $member->matrimony_status === 'married' || 
                          $member->marriage_date || 
                          $member->spouse_name || 
                          $member->husband_name || 
                          $member->wife_name;

        if (!$hasMarriageData) {
            $validation['is_valid'] = false;
            $validation['warnings'][] = 'Member has no marriage data recorded';
            return $validation;
        }

        // Validate each category of required fields
        $totalRequired = 0;
        $foundRequired = 0;

        foreach ($this->requiredFields as $category => $fields) {
            foreach ($fields as $field => $label) {
                $totalRequired++;
                $value = $this->getFieldValue($member, $field);
                
                if (!empty($value)) {
                    $foundRequired++;
                    $validation['field_mapping'][$field] = $value;
                } else {
                    $validation['missing_required'][] = [
                        'field' => $field,
                        'label' => $label,
                        'category' => $category
                    ];
                }
            }
        }

        // Check recommended fields
        $totalRecommended = count($this->recommendedFields);
        $foundRecommended = 0;

        foreach ($this->recommendedFields as $field => $label) {
            $value = $this->getFieldValue($member, $field);
            if (!empty($value)) {
                $foundRecommended++;
                $validation['field_mapping'][$field] = $value;
            } else {
                $validation['missing_optional'][] = [
                    'field' => $field,
                    'label' => $label
                ];
            }
        }

        // Check optional fields
        $totalOptional = count($this->optionalFields);
        $foundOptional = 0;

        foreach ($this->optionalFields as $field) {
            $value = $this->getFieldValue($member, $field);
            if (!empty($value)) {
                $foundOptional++;
                $validation['field_mapping'][$field] = $value;
            }
        }

        // Calculate completeness score
        $requiredScore = ($foundRequired / max($totalRequired, 1)) * 50; // 50% weight for required
        $recommendedScore = ($foundRecommended / max($totalRecommended, 1)) * 35; // 35% weight for recommended
        $optionalScore = ($foundOptional / max($totalOptional, 1)) * 15; // 15% weight for optional
        $validation['completeness_score'] = round($requiredScore + $recommendedScore + $optionalScore, 1);

        // Determine validity
        $validation['is_valid'] = count($validation['missing_required']) === 0;

        // Generate recommendations
        $this->generateRecommendations($member, $validation);

        return $validation;
    }

    /**
     * Get field value with fallback logic
     * 
     * @param Member $member
     * @param string $field
     * @return mixed
     */
    private function getFieldValue(Member $member, string $field)
    {
        // Direct field mapping
        if (isset($member->$field) && !empty($member->$field)) {
            return $member->$field;
        }

        // Fallback logic for common mappings
        return match($field) {
            'marriage_location' => $member->marriage_location ?? 'Sacred Heart Kandara Parish',
            
            // Husband details - try multiple sources
            'husband_name' => $member->husband_name ?? 
                ($member->gender === 'Male' ? 
                    ($member->first_name . ' ' . ($member->middle_name ? $member->middle_name . ' ' : '') . $member->last_name) : 
                    $member->spouse_name),
            'husband_age' => $member->husband_age ?? 
                ($member->gender === 'Male' && $member->date_of_birth ? 
                    \Carbon\Carbon::parse($member->date_of_birth)->age : 
                    $member->spouse_age),
            'husband_occupation' => $member->husband_occupation ?? 
                ($member->gender === 'Male' ? $member->occupation : $member->spouse_occupation),
            'husband_father_name' => $member->husband_father_name ?? 
                ($member->gender === 'Male' ? $member->father_name : $member->spouse_father_name),
            'husband_mother_name' => $member->husband_mother_name ?? 
                ($member->gender === 'Male' ? $member->mother_name : $member->spouse_mother_name),
            
            // Wife details - try multiple sources
            'wife_name' => $member->wife_name ?? 
                ($member->gender === 'Female' ? 
                    ($member->first_name . ' ' . ($member->middle_name ? $member->middle_name . ' ' : '') . $member->last_name) : 
                    $member->spouse_name),
            'wife_age' => $member->wife_age ?? 
                ($member->gender === 'Female' && $member->date_of_birth ? 
                    \Carbon\Carbon::parse($member->date_of_birth)->age : 
                    $member->spouse_age),
            'wife_occupation' => $member->wife_occupation ?? 
                ($member->gender === 'Female' ? $member->occupation : $member->spouse_occupation),
            'wife_father_name' => $member->wife_father_name ?? 
                ($member->gender === 'Female' ? $member->father_name : $member->spouse_father_name),
            'wife_mother_name' => $member->wife_mother_name ?? 
                ($member->gender === 'Female' ? $member->mother_name : $member->spouse_mother_name),
            
            // Ceremony details
            'religion' => $member->marriage_religion ?? 'Catholic',
            'presence_of' => $member->marriage_officiant_name ?? 'Rev. Parish Priest',
            
            // Witness mappings - map to actual database fields
            'male_witness_full_name' => $member->marriage_witness1_name ?? $member->witness_1_name,
            'female_witness_full_name' => $member->marriage_witness2_name ?? $member->witness_2_name,
            
            // Other mappings
            'civil_marriage_certificate_number' => $member->marriage_certificate_number,
            default => null
        };
    }

    /**
     * Generate actionable recommendations for improving data completeness
     * 
     * @param Member $member
     * @param array &$validation
     */
    private function generateRecommendations(Member $member, array &$validation): void
    {
        if ($validation['completeness_score'] < 50) {
            $validation['recommendations'][] = 'Critical: Certificate data is severely incomplete. Please update member marriage records.';
        } elseif ($validation['completeness_score'] < 75) {
            $validation['recommendations'][] = 'Warning: Certificate may have missing information. Consider updating member details.';
        }

        // Specific field recommendations
        if (empty($member->marriage_date)) {
            $validation['recommendations'][] = 'Add marriage date for accurate certificate generation.';
        }

        if (empty($member->husband_name) && empty($member->wife_name)) {
            $validation['recommendations'][] = 'Add spouse details for complete marriage record.';
        }

        if (empty($member->male_witness_full_name) || empty($member->female_witness_full_name)) {
            $validation['recommendations'][] = 'Add witness information for official certificate compliance.';
        }

        // Age calculation recommendations
        if ($member->gender === 'male' && empty($member->husband_age) && empty($member->date_of_birth)) {
            $validation['recommendations'][] = 'Add date of birth or husband age for certificate accuracy.';
        }

        if ($member->gender === 'female' && empty($member->wife_age) && empty($member->date_of_birth)) {
            $validation['recommendations'][] = 'Add date of birth or wife age for certificate accuracy.';
        }
    }

    /**
     * Get summary report for multiple members
     * 
     * @param Collection $members
     * @return array
     */
    public function generateSummaryReport(Collection $members): array
    {
        $report = [
            'total_members' => $members->count(),
            'valid_certificates' => 0,
            'incomplete_certificates' => 0,
            'average_completeness' => 0,
            'common_missing_fields' => [],
            'recommendations' => []
        ];

        $totalCompleteness = 0;
        $missingFieldCounts = [];

        foreach ($members as $member) {
            $validation = $this->validateMemberData($member);
            
            if ($validation['is_valid']) {
                $report['valid_certificates']++;
            } else {
                $report['incomplete_certificates']++;
            }

            $totalCompleteness += $validation['completeness_score'];

            // Count missing fields
            foreach ($validation['missing_required'] as $missing) {
                $field = $missing['field'];
                $missingFieldCounts[$field] = ($missingFieldCounts[$field] ?? 0) + 1;
            }
        }

        $report['average_completeness'] = round($totalCompleteness / max($members->count(), 1), 1);

        // Sort missing fields by frequency
        arsort($missingFieldCounts);
        $report['common_missing_fields'] = array_slice($missingFieldCounts, 0, 10, true);

        // Generate overall recommendations
        if ($report['average_completeness'] < 60) {
            $report['recommendations'][] = 'System-wide data quality issue detected. Review member data entry processes.';
        }

        if ($report['incomplete_certificates'] > $report['valid_certificates']) {
            $report['recommendations'][] = 'Majority of marriage records are incomplete. Prioritize data completion.';
        }

        return $report;
    }
}