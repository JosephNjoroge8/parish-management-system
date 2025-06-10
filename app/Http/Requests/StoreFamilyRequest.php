<?php
// app/Http/Requests/StoreFamilyRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFamilyRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'family_name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'deanery' => 'required|string|max:255',
            'parish' => 'required|string|max:255',
            'head_of_family_id' => 'nullable|exists:members,id',
        ];
    }

    public function messages()
    {
        return [
            'family_name.required' => 'Family name is required.',
            'address.required' => 'Address is required.',
            'email.email' => 'Please enter a valid email address.',
            'deanery.required' => 'Deanery is required.',
            'parish.required' => 'Parish is required.',
            'head_of_family_id.exists' => 'Selected head of family does not exist.',
        ];
    }
}