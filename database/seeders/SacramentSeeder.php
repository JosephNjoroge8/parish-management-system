<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sacrament;
use App\Models\Member;
use App\Models\User;

class SacramentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::first();
        $members = Member::all();

        if ($members->isEmpty()) {
            $this->command->info('No members found. Please run MemberSeeder first.');
            return;
        }

        // Clear existing sacrament data to avoid duplicates
        Sacrament::truncate();
        $this->command->info('Cleared existing sacrament records.');

        // Find specific members by name for realistic sacrament records
        $joseph = $members->where('first_name', 'Joseph')->where('last_name', 'Njoroge')->first();
        $grace = $members->where('first_name', 'Grace')->where('last_name', 'Njoroge')->first();
        $john = $members->where('first_name', 'John')->where('last_name', 'Njoroge')->first();
        $mary = $members->where('first_name', 'Mary')->where('last_name', 'Wanjiku')->first();
        $david = $members->where('first_name', 'David')->where('last_name', 'Wanjiku')->first();

        $sacraments = [];

        // Joseph Njoroge sacraments
        if ($joseph) {
            $sacraments = array_merge($sacraments, [
                [
                    'member_id' => $joseph->id,
                    'sacrament_type' => 'baptism',
                    'sacrament_date' => '1986-01-10',
                    'celebrant' => 'Fr. Michael Johnson',
                    'location' => 'St James Kangemi Parish',
                    'certificate_number' => 'BAP001-1986',
                    'book_number' => 'BAP-001',
                    'page_number' => '15',
                    'notes' => 'Baptized as infant',
                    'godparent_1' => 'Peter Kamau',
                    'godparent_2' => 'Mary Kamau',
                    'recorded_by' => $admin ? $admin->id : null,
                ],
                [
                    'member_id' => $joseph->id,
                    'sacrament_type' => 'eucharist',
                    'sacrament_date' => '1993-05-15',
                    'celebrant' => 'Fr. Paul Wanjala',
                    'location' => 'St James Kangemi Parish',
                    'certificate_number' => 'FC001-1993',
                    'book_number' => 'FC-001',
                    'page_number' => '8',
                    'notes' => 'First Holy Communion',
                    'recorded_by' => $admin ? $admin->id : null,
                ],
                [
                    'member_id' => $joseph->id,
                    'sacrament_type' => 'confirmation',
                    'sacrament_date' => '1998-05-15',
                    'celebrant' => 'Bishop Peter Njenga',
                    'location' => 'St James Kangemi Parish',
                    'certificate_number' => 'CON001-1998',
                    'book_number' => 'CON-001',
                    'page_number' => '25',
                    'notes' => 'Confirmed by Bishop',
                    'recorded_by' => $admin ? $admin->id : null,
                ],
            ]);
        }

        // Grace Njoroge sacraments
        if ($grace) {
            $sacraments = array_merge($sacraments, [
                [
                    'member_id' => $grace->id,
                    'sacrament_type' => 'baptism',
                    'sacrament_date' => '1989-02-18',
                    'celebrant' => 'Fr. Paul Wanjala',
                    'location' => 'St James Kangemi Parish',
                    'certificate_number' => 'BAP002-1989',
                    'book_number' => 'BAP-001',
                    'page_number' => '42',
                    'notes' => 'Baptized as infant',
                    'godparent_1' => 'Mary Wanjiku',
                    'godparent_2' => 'Samuel Kariuki',
                    'recorded_by' => $admin ? $admin->id : null,
                ],
                [
                    'member_id' => $grace->id,
                    'sacrament_type' => 'eucharist',
                    'sacrament_date' => '1996-04-14',
                    'celebrant' => 'Fr. John Mukuria',
                    'location' => 'St James Kangemi Parish',
                    'certificate_number' => 'FC002-1996',
                    'book_number' => 'FC-001',
                    'page_number' => '15',
                    'notes' => 'First Holy Communion',
                    'recorded_by' => $admin ? $admin->id : null,
                ],
                [
                    'member_id' => $grace->id,
                    'sacrament_type' => 'confirmation',
                    'sacrament_date' => '2001-04-22',
                    'celebrant' => 'Bishop Francis Kimani',
                    'location' => 'St James Kangemi Parish',
                    'certificate_number' => 'CON002-2001',
                    'book_number' => 'CON-001',
                    'page_number' => '78',
                    'notes' => 'Confirmed with class of 2001',
                    'recorded_by' => $admin ? $admin->id : null,
                ],
            ]);
        }

        // John Njoroge (child) sacraments
        if ($john) {
            $sacraments = array_merge($sacraments, [
                [
                    'member_id' => $john->id,
                    'sacrament_type' => 'baptism',
                    'sacrament_date' => '2010-04-25',
                    'celebrant' => 'Fr. John Mukuria',
                    'location' => 'St James Kangemi Parish',
                    'certificate_number' => 'BAP003-2010',
                    'book_number' => 'BAP-002',
                    'page_number' => '12',
                    'notes' => 'Child baptism',
                    'godparent_1' => 'Joseph Njoroge',
                    'godparent_2' => 'Grace Njoroge',
                    'recorded_by' => $admin ? $admin->id : null,
                ],
            ]);
        }

        // Mary Wanjiku sacraments
        if ($mary) {
            $sacraments = array_merge($sacraments, [
                [
                    'member_id' => $mary->id,
                    'sacrament_type' => 'baptism',
                    'sacrament_date' => '1983-03-20',
                    'celebrant' => 'Fr. Patrick Muriuki',
                    'location' => 'St Veronica Pembe Tatu Parish',
                    'certificate_number' => 'BAP004-1983',
                    'book_number' => 'BAP-001',
                    'page_number' => '85',
                    'notes' => 'Baptized as infant',
                    'godparent_1' => 'Agnes Muthoni',
                    'godparent_2' => 'Paul Mwangi',
                    'recorded_by' => $admin ? $admin->id : null,
                ],
                [
                    'member_id' => $mary->id,
                    'sacrament_type' => 'confirmation',
                    'sacrament_date' => '1995-06-11',
                    'celebrant' => 'Bishop Michael Kiarie',
                    'location' => 'St Veronica Pembe Tatu Parish',
                    'certificate_number' => 'CON003-1995',
                    'book_number' => 'CON-001',
                    'page_number' => '45',
                    'notes' => 'Confirmed at age 13',
                    'recorded_by' => $admin ? $admin->id : null,
                ],
            ]);
        }

        // David Wanjiku sacraments
        if ($david) {
            $sacraments = array_merge($sacraments, [
                [
                    'member_id' => $david->id,
                    'sacrament_type' => 'baptism',
                    'sacrament_date' => '2008-07-15',
                    'celebrant' => 'Fr. Patrick Muriuki',
                    'location' => 'St Veronica Pembe Tatu Parish',
                    'certificate_number' => 'BAP005-2008',
                    'book_number' => 'BAP-002',
                    'page_number' => '30',
                    'notes' => 'Child baptism',
                    'godparent_1' => 'Mary Wanjiku',
                    'godparent_2' => 'Samuel Kariuki',
                    'recorded_by' => $admin ? $admin->id : null,
                ],
                [
                    'member_id' => $david->id,
                    'sacrament_type' => 'confirmation',
                    'sacrament_date' => '2021-11-28',
                    'celebrant' => 'Bishop Joseph Mburu',
                    'location' => 'St Veronica Pembe Tatu Parish',
                    'certificate_number' => 'CON004-2021',
                    'book_number' => 'CON-002',
                    'page_number' => '18',
                    'notes' => 'Confirmed at age 13',
                    'recorded_by' => $admin ? $admin->id : null,
                ],
            ]);
        }

        // Add marriage sacrament for Joseph and Grace (if both exist)
        if ($joseph && $grace) {
            $sacraments[] = [
                'member_id' => $joseph->id,
                'sacrament_type' => 'marriage',
                'sacrament_date' => '2015-12-26',
                'celebrant' => 'Fr. John Mukuria',
                'location' => 'St James Kangemi Parish',
                'certificate_number' => 'MAR001-2015',
                'book_number' => 'MAR-001',
                'page_number' => '5',
                'notes' => 'Wedding of Joseph Njoroge and Grace Njoroge',
                'witness_1' => 'Samuel Kariuki',
                'witness_2' => 'Mary Wanjiku',
                'recorded_by' => $admin ? $admin->id : null,
            ];
        }

        // Create sacrament records
        foreach ($sacraments as $sacrament) {
            try {
                Sacrament::create($sacrament);
            } catch (\Exception $e) {
                $this->command->error('Failed to create sacrament: ' . $e->getMessage());
            }
        }

        $this->command->info('Created ' . count($sacraments) . ' sacrament records.');
    }
}
