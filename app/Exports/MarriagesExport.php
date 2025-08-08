<?php

namespace App\Exports;

use App\Models\Member;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MarriagesExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Member::whereNotNull('marriage_date')
            ->select('first_name', 'last_name', 'email', 'marriage_date', 'spouse_name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'First Name',
            'Last Name',
            'Email',
            'Marriage Date',
            'Spouse Name'
        ];
    }
}
