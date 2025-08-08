<?php

            namespace App\Exports;

            use Maatwebsite\Excel\Concerns\FromCollection;
            use Maatwebsite\Excel\Concerns\WithHeadings;
            use Maatwebsite\Excel\Concerns\WithMapping;
            use App\Models\Member;

            class SacramentsExport implements FromCollection, WithHeadings, WithMapping
            {
                public function collection()
                {
                    return Member::whereNotNull('baptism_date')
                        ->orWhereNotNull('confirmation_date')
                        ->orWhereNotNull('first_communion_date')
                        ->get();
                }

                public function headings(): array
                {
                    return [
                        'Member ID',
                        'Name',
                        'Baptism Date',
                        'Confirmation Date',
                        'First Communion Date',
                    ];
                }

                public function map($member): array
                {
                    return [
                        $member->id,
                        $member->first_name . ' ' . $member->last_name,
                        $member->baptism_date,
                        $member->confirmation_date,
                        $member->first_communion_date,
                    ];
                }
            }