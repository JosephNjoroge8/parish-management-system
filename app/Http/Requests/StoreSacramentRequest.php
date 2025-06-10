<?php
// app/Http/Requests/StoreSacramentRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSacramentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'member_id' => 'required|exists:members,id',
            'sacrament_type' => 'required|in:baptism,confirmation,first_communion,matrimony,holy_orders,anointing_of_sick',
            'sacrament_date' => 'required|date',
            'location' => 'required|string|max:255',
            'officiant' => 'required|string|max:255',
            'certificate_number' => 'nullable|string|unique:sacraments,certificate_number',
            'witnesses' => 'nullable|string',
            'special_notes' => 'nullable|string',
            'book_reference' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'member_id.required' => 'Member selection is required.',
            'member_id.exists' => 'Selected member does not exist.',
            'sacrament_type.required' => 'Sacrament type is required.',
            'sacrament_type.in' => 'Invalid sacrament type selected.',
            'sacrament_date.required' => 'Sacrament date is required.',
            'sacrament_date.date' => 'Please enter a valid date.',
            'location.required' => 'Location is required.',
            'officiant.required' => 'Officiant name is required.',
            'certificate_number.unique' => 'This certificate number already exists.',
        ];
    }
}
