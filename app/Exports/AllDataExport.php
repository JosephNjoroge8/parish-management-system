<?php

namespace App\Exports;

use App\Models\Member;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AllDataExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return Collection
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
