<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isSuperAdminByEmail();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Core personal information
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:Male,Female',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:members,email',
            'id_number' => 'nullable|string|max:20|unique:members,id_number',
            'residence' => 'nullable|string|max:500',

            // Church information
            'local_church' => 'required|string|max:255',
            'small_christian_community' => 'nullable|string|max:255',
            'church_group' => 'required|string|max:255',
            'additional_church_groups' => 'nullable|array',
            'additional_church_groups.*' => 'string|max:255',

            // Membership information
            'membership_status' => 'required|in:active,inactive,transferred,deceased',
            'membership_date' => 'nullable|date',
            'matrimony_status' => 'required|string|max:255', // Changed to string to allow user input
            'marriage_type' => 'nullable|in:church,civil,customary,come_we_stay',
            'occupation' => 'nullable|string|max:255',
            'education_level' => 'nullable|in:none,primary,kcpe,secondary,kcse,certificate,diploma,degree,masters,phd',

            // Family and relationships
            'family_id' => 'nullable|exists:families,id',
            'parent' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'godparent' => 'nullable|string|max:255',
            'minister' => 'nullable|string|max:255',
            'tribe' => 'nullable|string|max:255',
            'clan' => 'nullable|string|max:255',

            // Disability information
            'is_differently_abled' => 'boolean',
            'disability_description' => 'nullable|string|max:1000',

            // Sacrament information - with shared data validation
            'baptism_date' => 'nullable|date',
            'baptism_location' => 'nullable|string|max:255',
            'baptized_by' => 'nullable|string|max:255',
            'sponsor' => 'nullable|string|max:255',
            'confirmation_date' => 'nullable|date|after_or_equal:baptism_date',
            'confirmation_location' => 'nullable|string|max:255',
            'confirmation_register_number' => 'nullable|string|max:50|unique:members,confirmation_register_number',
            'confirmation_number' => 'nullable|string|max:50|unique:members,confirmation_number',
            'eucharist_date' => 'nullable|date',
            'eucharist_location' => 'nullable|string|max:255',

            // Marriage information - only required for church marriages
            'marriage_date' => [
                'nullable',
                'date',
                Rule::requiredIf(function () {
                    return $this->matrimony_status === 'married' && $this->marriage_type === 'church';
                }),
            ],
            'marriage_location' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(function () {
                    return $this->matrimony_status === 'married' && $this->marriage_type === 'church';
                }),
            ],
            'marriage_county' => 'nullable|string|max:255',
            'marriage_sub_county' => 'nullable|string|max:255',
            'marriage_entry_number' => 'nullable|string|max:50|unique:members,marriage_entry_number',
            'marriage_certificate_number' => 'nullable|string|max:50|unique:members,marriage_certificate_number',
            'marriage_religion' => 'nullable|string|max:255',
            'marriage_license_number' => 'nullable|string|max:50',
            'marriage_officiant_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(function () {
                    return $this->matrimony_status === 'married' && $this->marriage_type === 'church';
                }),
            ],
            'marriage_witness1_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(function () {
                    return $this->matrimony_status === 'married' && $this->marriage_type === 'church';
                }),
            ],
            'marriage_witness2_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(function () {
                    return $this->matrimony_status === 'married' && $this->marriage_type === 'church';
                }),
            ],

            // Spouse Information - only for married members
            'spouse_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(function () {
                    return $this->matrimony_status === 'married';
                }),
            ],
            'spouse_age' => 'nullable|integer|min:18|max:120',
            'spouse_residence' => 'nullable|string|max:500',
            'spouse_county' => 'nullable|string|max:255',
            'spouse_marital_status' => 'nullable|string|max:255',
            'spouse_occupation' => 'nullable|string|max:255',
            'spouse_father_name' => 'nullable|string|max:255',
            'spouse_father_occupation' => 'nullable|string|max:255',
            'spouse_father_residence' => 'nullable|string|max:500',
            'spouse_mother_name' => 'nullable|string|max:255',
            'spouse_mother_occupation' => 'nullable|string|max:255',
            'spouse_mother_residence' => 'nullable|string|max:500',

            // Location fields
            'birth_village' => 'nullable|string|max:255',
            'county' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',

            // Additional family fields
            'godfather_name' => 'nullable|string|max:255',
            'godmother_name' => 'nullable|string|max:255',

            // Notes
            'notes' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'date_of_birth.required' => 'Date of birth is required.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
            'gender.required' => 'Gender selection is required.',
            'gender.in' => 'Gender must be either Male or Female.',
            'local_church.required' => 'Local church is required.',
            'church_group.required' => 'Church group is required.',
            'membership_status.required' => 'Membership status is required.',
            'matrimony_status.required' => 'Matrimony status is required.',
            'email.unique' => 'This email address is already registered.',
            'id_number.unique' => 'This ID number is already registered.',
            'confirmation_date.after_or_equal' => 'Confirmation date must be on or after baptism date.',
            'confirmation_register_number.unique' => 'This confirmation register number is already in use.',
            'confirmation_number.unique' => 'This confirmation number is already in use.',
            'marriage_entry_number.unique' => 'This marriage entry number is already in use.',
            'marriage_certificate_number.unique' => 'This marriage certificate number is already in use.',
            'marriage_date.required' => 'Marriage date is required for church marriages.',
            'marriage_location.required' => 'Marriage location is required for church marriages.',
            'marriage_officiant_name.required' => 'Marriage officiant name is required for church marriages.',
            'marriage_witness1_name.required' => 'First witness name is required for church marriages.',
            'marriage_witness2_name.required' => 'Second witness name is required for church marriages.',
            'spouse_name.required' => 'Spouse name is required for married members.',
            'spouse_age.min' => 'Spouse age must be at least 18 years.',
            'spouse_age.max' => 'Spouse age cannot exceed 120 years.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate church group gender restrictions
            $this->validateChurchGroupGender($validator);

            // Validate age-appropriate church groups
            $this->validateAgeAppropriateGroups($validator);

            // Validate sacrament data sharing
            $this->validateSacramentDataSharing($validator);
        });
    }

    /**
     * Validate church group gender restrictions.
     */
    protected function validateChurchGroupGender($validator): void
    {
        $gender = $this->input('gender');
        $churchGroup = $this->input('church_group');

        if ($churchGroup === 'C.W.A' && $gender !== 'Female') {
            $validator->errors()->add('church_group', 'C.W.A membership is restricted to female members only.');
        }

        if ($churchGroup === 'CMA' && $gender !== 'Male') {
            $validator->errors()->add('church_group', 'CMA membership is restricted to male members only.');
        }
    }

    /**
     * Validate age-appropriate church groups.
     */
    protected function validateAgeAppropriateGroups($validator): void
    {
        $dateOfBirth = $this->input('date_of_birth');
        $churchGroup = $this->input('church_group');

        if ($dateOfBirth && $churchGroup) {
            $age = now()->diffInYears($dateOfBirth);

            if ($churchGroup === 'PMC' && $age > 12) {
                $validator->errors()->add('church_group', 'PMC membership is for children 12 years and below.');
            }

            if ($churchGroup === 'Youth' && ($age < 13 || $age > 24)) {
                $validator->errors()->add('church_group', 'Youth group membership is for ages 13-24.');
            }
        }
    }

    /**
     * Validate that baptism and confirmation can share common data.
     */
    protected function validateSacramentDataSharing($validator): void
    {
        // If baptism location is provided but confirmation location is not,
        // we'll auto-populate it in the controller

        // If baptized_by is provided but minister is not, we'll auto-populate it

        // If sponsor is provided but godparent is not, we'll auto-populate it

        // This validation ensures data consistency without forcing duplication
    }

    /**
     * Get the validated data with auto-populated shared fields.
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        // Auto-populate shared sacrament data
        if (! empty($data['baptism_location']) && empty($data['confirmation_location'])) {
            $data['confirmation_location'] = $data['baptism_location'];
        }

        if (! empty($data['baptized_by']) && empty($data['minister'])) {
            $data['minister'] = $data['baptized_by'];
        }

        if (! empty($data['sponsor']) && empty($data['godparent'])) {
            $data['godparent'] = $data['sponsor'];
        }

        return $data;
    }
}
