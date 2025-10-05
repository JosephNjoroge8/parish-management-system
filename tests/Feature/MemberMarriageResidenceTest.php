<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberMarriageResidenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['is_admin' => true]);
    }

    /** @test */
    public function member_marriage_residence_is_required_for_married_members()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('members.store'), [
            'first_name' => 'John',
            'last_name' => 'TestMarriageResidence',
            'gender' => 'Male',
            'matrimony_status' => 'married',
            'marriage_type' => 'church',
            'local_church' => 'Sacred Heart Kandara',
            'church_group' => 'Catholic Action',
            'small_christian_community' => 'Test Community',
            'occupation' => 'employed',
            'education_level' => 'degree',
            'membership_date' => now()->format('Y-m-d'),
            'membership_status' => 'active',

            // Required marriage fields
            'bride_name' => 'Jane TestMarriageResidence',
            'marriage_date' => '2020-06-20',
            'marriage_location' => 'Sacred Heart Church',
            'marriage_county' => 'Murang\'a',
            'marriage_sub_county' => 'Kandara',

            // Required parent fields for married members
            'father_occupation' => 'Teacher',
            'father_residence' => 'Murang\'a',
            'mother_occupation' => 'Nurse',
            'mother_residence' => 'Murang\'a',

            // Missing member_marriage_residence intentionally
        ]);

        $response->assertSessionHasErrors(['member_marriage_residence']);
    }

    /** @test */
    public function member_marriage_residence_is_not_required_for_single_members()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('members.store'), [
            'first_name' => 'Jane',
            'last_name' => 'TestSingle',
            'gender' => 'Female',
            'matrimony_status' => 'single',
            'local_church' => 'Sacred Heart Kandara',
            'church_group' => 'Catholic Action',
            'small_christian_community' => 'Test Community',
            'occupation' => 'not_employed',
            'education_level' => 'none',
            'membership_date' => now()->format('Y-m-d'),
            'membership_status' => 'active',
            // No member_marriage_residence field - should be fine for single members
        ]);

        $response->assertStatus(302); // Accept any redirect for successful creation
        $this->assertDatabaseHas('members', [
            'first_name' => 'Jane',
            'last_name' => 'TestSingle',
            'matrimony_status' => 'single',
            'member_marriage_residence' => null,
        ]);
    }

    /** @test */
    public function married_member_can_be_created_with_marriage_residence()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('members.store'), [
            'first_name' => 'John',
            'last_name' => 'TestMarriageResidenceSuccess',
            'gender' => 'Male',
            'matrimony_status' => 'married',
            'marriage_type' => 'church',
            'local_church' => 'Sacred Heart Kandara',
            'church_group' => 'Catholic Action',
            'small_christian_community' => 'Test Community',
            'occupation' => 'not_employed',
            'education_level' => 'none',
            'membership_date' => now()->format('Y-m-d'),
            'membership_status' => 'active',

            // Required marriage fields
            'bride_name' => 'Jane TestMarriageResidenceSuccess',
            'marriage_date' => '2020-06-20',
            'marriage_location' => 'Sacred Heart Church',
            'marriage_county' => 'Murang\'a',
            'marriage_sub_county' => 'Kandara',
            'member_marriage_residence' => 'Nairobi Central at time of marriage',

            // Required parent fields for married members
            'father_occupation' => 'Teacher',
            'father_residence' => 'Murang\'a',
            'mother_occupation' => 'Nurse',
            'mother_residence' => 'Murang\'a',
        ]);

        $response->assertStatus(302); // Accept any redirect for successful creation
        $this->assertDatabaseHas('members', [
            'first_name' => 'John',
            'last_name' => 'TestMarriageResidenceSuccess',
            'matrimony_status' => 'married',
            'member_marriage_residence' => 'Nairobi Central at time of marriage',
        ]);
    }

    public function test_member_marriage_residence_can_be_updated()
    {
        $this->actingAs($this->user);

        $member = Member::factory()->create([
            'matrimony_status' => 'married',
            'marriage_type' => 'church',
            'member_marriage_residence' => 'Original residence',
            'local_church' => 'Sacred Heart Kandara',
            'church_group' => 'Catholic Action',
            'occupation' => 'not_employed',
            'education_level' => 'none',
            'gender' => 'Male',
        ]);

        $updateData = [
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'gender' => $member->gender,
            'matrimony_status' => 'married',
            'marriage_type' => 'church',
            'local_church' => $member->local_church,
            'church_group' => $member->church_group,
            'occupation' => $member->occupation,
            'education_level' => $member->education_level,

            // Required fields for married members
            'bride_name' => 'Jane Updated',
            'marriage_date' => '2020-06-20',
            'marriage_location' => 'Sacred Heart Church',
            'marriage_county' => 'Murang\'a',
            'marriage_sub_county' => 'Kandara',
            'member_marriage_residence' => 'Updated residence at marriage time',
        ];

        $response = $this->put(route('members.update', $member), $updateData);

        $response->assertStatus(302); // Accept any redirect for successful update

        // Refresh the member from database
        $member->refresh();
        $this->assertEquals('Updated residence at marriage time', $member->member_marriage_residence);
    }

    /** @test */
    public function member_marriage_residence_is_required_when_updating_to_married_status()
    {
        $this->actingAs($this->user);

        $member = Member::factory()->create([
            'matrimony_status' => 'single',
            'member_marriage_residence' => null,
            'local_church' => 'Sacred Heart Kandara',
            'church_group' => 'Catholic Action',
            'occupation' => 'not_employed',
            'education_level' => 'none',
        ]);

        $response = $this->put(route('members.update', $member), [
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'gender' => $member->gender,
            'matrimony_status' => 'married', // Changing to married
            'marriage_type' => 'church',
            'local_church' => $member->local_church,
            'church_group' => $member->church_group,
            'occupation' => $member->occupation,
            'education_level' => $member->education_level,

            // Required fields for married members
            'bride_name' => 'Jane Updated',
            'marriage_date' => '2020-06-20',
            'marriage_location' => 'Sacred Heart Church',
            'marriage_county' => 'Murang\'a',
            'marriage_sub_county' => 'Kandara',
            // Missing member_marriage_residence intentionally
        ]);

        $response->assertSessionHasErrors(['member_marriage_residence']);
    }
}
