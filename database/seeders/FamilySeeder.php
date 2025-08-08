<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Family;
use App\Models\User;

class FamilySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::first();

        $families = [
            [
                'family_name' => 'The Njoroge Family',
                'family_code' => 'FAM001',
                'head_of_family' => 'Joseph Mwangi Njoroge', // String field as per migration
                'address' => 'P.O. Box 123, Kangemi, Nairobi',
                'phone' => '+254701234567',
                'email' => 'njoroge.family@gmail.com',
                'deanery' => 'Nairobi West Deanery',
                'parish' => 'St James Kangemi Parish',
                'parish_section' => 'Kangemi Section',
                'created_by' => $admin ? $admin->id : null,
            ],
            [
                'family_name' => 'The Wanjiku Family',
                'family_code' => 'FAM002',
                'head_of_family' => 'Mary Nyokabi Wanjiku',
                'address' => 'P.O. Box 456, Pembe Tatu, Kiambu',
                'phone' => '+254712345678',
                'email' => 'wanjiku.family@yahoo.com',
                'deanery' => 'Kiambu Deanery',
                'parish' => 'St Veronica Pembe Tatu Parish',
                'parish_section' => 'Pembe Tatu Section',
                'created_by' => $admin ? $admin->id : null,
            ],
            [
                'family_name' => 'The Mutua Family',
                'family_code' => 'FAM003',
                'head_of_family' => 'Peter Musyoki Mutua',
                'address' => 'P.O. Box 789, Cathedral, Nairobi',
                'phone' => '+254723456789',
                'email' => 'mutua.family@gmail.com',
                'deanery' => 'Nairobi Central Deanery',
                'parish' => 'Our Lady of Consolata Cathedral Parish',
                'parish_section' => 'Cathedral Section',
                'created_by' => $admin ? $admin->id : null,
            ],
            [
                'family_name' => 'The Ochieng Family',
                'family_code' => 'FAM004',
                'head_of_family' => 'James Otieno Ochieng',
                'address' => 'P.O. Box 321, Kiawara, Kiambu',
                'phone' => '+254734567890',
                'email' => 'ochieng.family@hotmail.com',
                'deanery' => 'Kiambu Deanery',
                'parish' => 'St Peter Kiawara Parish',
                'parish_section' => 'Kiawara Section',
                'created_by' => $admin ? $admin->id : null,
            ],
            [
                'family_name' => 'The Akinyi Family',
                'family_code' => 'FAM005',
                'head_of_family' => 'Grace Adhiambo Akinyi',
                'address' => 'P.O. Box 654, Kandara, Murang\'a',
                'phone' => '+254745678901',
                'email' => 'akinyi.family@gmail.com',
                'deanery' => 'Murang\'a Deanery',
                'parish' => 'Sacred Heart Kandara Parish',
                'parish_section' => 'Kandara Section',
                'created_by' => $admin ? $admin->id : null,
            ],
        ];

        foreach ($families as $family) {
            Family::create($family);
        }
    }
}
