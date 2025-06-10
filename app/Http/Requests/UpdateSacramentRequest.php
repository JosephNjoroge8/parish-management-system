<?php
// app/Http/Requests/UpdateSacramentRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSacramentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

        public function rules()
        {
            $sacramentId = $this->route('sacrament')->id;
    
            return [
                'member_id' => 'required|exists:members,id',
                'sacrament_type' => 'required|in:baptism,confirmation,first_communion,matrimony,holy_orders,anointing_of_sick',
                'sacrament_date' => 'required|date',
                'location' => 'required|string|max:255',
            ];
        }
    }