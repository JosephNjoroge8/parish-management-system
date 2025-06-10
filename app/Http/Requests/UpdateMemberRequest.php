<?php

// app/Http/Requests/UpdateMemberRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $memberId = $this->route('member')->id;

        return [
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female',
            'phone' => 'nullable|string|max:20',
            'email' => [
                'nullable',
                'email',
                Rule::unique('members', 'email')->ignore($memberId)
            ],
            'id_number' => [
                'nullable',
                'string',
                Rule::unique('members', 'id_number')->ignore($memberId)
            ],
            'address' => 'required|string',
            'occupation' => 'nullable|string|max:255',
            'marital_status' => 'required|in:single,married,divorced,widowed',
            'membership_date' => 'required|date',
            'membership_status' => 'required|in:active,inactive,transferred',
            'family_id' => 'required|exists:families,id',
            'relationship_to_head' => 'nullable|string|max:100',
            'special_needs' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'date_of_birth.required' => 'Date of birth is required.',
            'date_of_birth.before' => 'Date of birth must be before today.',
            'gender.required' => 'Gender is required.',
            'gender.in' => 'Gender must be either male or female.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'id_number.unique' => 'This ID number is already registered.',
            'address.required' => 'Address is required.',
            'marital_status.required' => 'Marital status is required.',
            'membership_date.required' => 'Membership date is required.',
            'membership_status.required' => 'Membership status is required.',
            'family_id.required' => 'Family selection is required.',
            'family_id.exists' => 'Selected family does not exist.',
        ];
    }
}