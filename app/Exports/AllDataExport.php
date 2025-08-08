<?php

namespace App\Exports;

use App\Models\Member;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Facades\Schema;

class AllDataExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Member::all();
    }

    public function headings(): array
    {
        $columns = Schema::getColumnListing('members');
        return array_map('ucfirst', $columns);
    }

    public function map($member): array
    {
        $columns = Schema::getColumnListing('members');
        $data = [];
        
        foreach ($columns as $column) {
            $data[] = $member->$column ?? '';
        }
        
        return $data;
    }
}
