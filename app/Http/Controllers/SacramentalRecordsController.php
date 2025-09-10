<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Family;
use App\Models\MarriageRecord;
use App\Models\BaptismRecord;
use App\Models\Sacrament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class SacramentalRecordsController extends Controller
{
    /**
     * Store a new baptism record for a member.
     */
    public function storeBaptismRecord(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'father_name' => 'required|string|max:255',
            'mother_name' => 'required|string|max:255',
            'tribe' => 'required|string|max:255',
            'birth_village' => 'required|string|max:255',
            'county' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'residence' => 'required|string|max:255',
            'baptism_location' => 'required|string|max:255',
            'baptism_date' => 'required|date',
            'baptized_by' => 'required|string|max:255',
            'sponsor' => 'required|string|max:255',
            'eucharist_location' => 'nullable|string|max:255',
            'eucharist_date' => 'nullable|date',
            'confirmation_location' => 'nullable|string|max:255',
            'confirmation_date' => 'nullable|date',
            'confirmation_number' => 'nullable|string|max:50',
            'confirmation_register_number' => 'nullable|string|max:50',
            'marriage_spouse' => 'nullable|string|max:255',
            'marriage_location' => 'nullable|string|max:255',
            'marriage_date' => 'nullable|date',
            'marriage_register_number' => 'nullable|string|max:50',
            'marriage_number' => 'nullable|string|max:50',
        ]);
        
        try {
            DB::beginTransaction();
            
            $member = Member::findOrFail($validated['member_id']);
            
            // Create the baptism sacrament record
            $baptismSacrament = new Sacrament([
                'member_id' => $member->id,
                'sacrament_type' => 'baptism',
                'sacrament_date' => $validated['baptism_date'],
                'location' => $validated['baptism_location'],
                'celebrant' => $validated['baptized_by'],
                'godparent_1' => $validated['sponsor'],
                'certificate_number' => $request->certificate_number ?? null,
                'book_number' => $request->book_number ?? null,
                'page_number' => $request->page_number ?? null,
                'notes' => $request->notes ?? null,
                'recorded_by' => Auth::check() ? Auth::id() : null,
            ]);
            
            $baptismSacrament->save();
            
            // Create eucharist sacrament record if date provided
            $eucharistSacrament = null;
            if (!empty($validated['eucharist_date']) && !empty($validated['eucharist_location'])) {
                $eucharistSacrament = new Sacrament([
                    'member_id' => $member->id,
                    'sacrament_type' => 'eucharist',
                    'sacrament_date' => $validated['eucharist_date'],
                    'location' => $validated['eucharist_location'],
                    'recorded_by' => Auth::id(),
                ]);
                
                $eucharistSacrament->save();
            }
            
            // Create confirmation sacrament record if date provided
            $confirmationSacrament = null;
            if (!empty($validated['confirmation_date']) && !empty($validated['confirmation_location'])) {
                $confirmationSacrament = new Sacrament([
                    'member_id' => $member->id,
                    'sacrament_type' => 'confirmation',
                    'sacrament_date' => $validated['confirmation_date'],
                    'location' => $validated['confirmation_location'],
                    'certificate_number' => $validated['confirmation_number'] ?? null,
                    'book_number' => $validated['confirmation_register_number'] ?? null,
                    'recorded_by' => Auth::id(),
                ]);
                
                $confirmationSacrament->save();
            }
            
            // Create marriage sacrament record if date provided
            $marriageSacrament = null;
            if (!empty($validated['marriage_date']) && !empty($validated['marriage_location'])) {
                $marriageSacrament = new Sacrament([
                    'member_id' => $member->id,
                    'sacrament_type' => 'marriage',
                    'sacrament_date' => $validated['marriage_date'],
                    'location' => $validated['marriage_location'],
                    'certificate_number' => $validated['marriage_number'] ?? null,
                    'book_number' => $validated['marriage_register_number'] ?? null,
                    'witness_1' => $validated['marriage_spouse'] ?? null,
                    'recorded_by' => Auth::id(),
                ]);
                
                $marriageSacrament->save();
            }
            
            // Generate a unique record number for the baptism record
            $recordNumber = BaptismRecord::generateRecordNumber();
            
            // Create the detailed baptism record
            $baptismRecord = new BaptismRecord([
                'record_number' => $recordNumber,
                'father_name' => $validated['father_name'],
                'mother_name' => $validated['mother_name'],
                'tribe' => $validated['tribe'],
                'birth_village' => $validated['birth_village'],
                'county' => $validated['county'],
                'birth_date' => $validated['birth_date'],
                'residence' => $validated['residence'],
                'baptism_location' => $validated['baptism_location'],
                'baptism_date' => $validated['baptism_date'],
                'baptized_by' => $validated['baptized_by'],
                'sponsor' => $validated['sponsor'],
                'eucharist_location' => $validated['eucharist_location'] ?? null,
                'eucharist_date' => $validated['eucharist_date'] ?? null,
                'confirmation_location' => $validated['confirmation_location'] ?? null,
                'confirmation_date' => $validated['confirmation_date'] ?? null,
                'confirmation_number' => $validated['confirmation_number'] ?? null,
                'confirmation_register_number' => $validated['confirmation_register_number'] ?? null,
                'marriage_spouse' => $validated['marriage_spouse'] ?? null,
                'marriage_location' => $validated['marriage_location'] ?? null,
                'marriage_date' => $validated['marriage_date'] ?? null,
                'marriage_register_number' => $validated['marriage_register_number'] ?? null,
                'marriage_number' => $validated['marriage_number'] ?? null,
                'member_id' => $member->id,
                'baptism_sacrament_id' => $baptismSacrament->id,
                'eucharist_sacrament_id' => $eucharistSacrament ? $eucharistSacrament->id : null,
                'confirmation_sacrament_id' => $confirmationSacrament ? $confirmationSacrament->id : null,
                'marriage_sacrament_id' => $marriageSacrament ? $marriageSacrament->id : null,
            ]);
            
            $baptismRecord->save();
            
            // Link baptism record to sacrament records
            $baptismSacrament->detailed_record_type = BaptismRecord::class;
            $baptismSacrament->detailed_record_id = $baptismRecord->id;
            $baptismSacrament->save();
            
            // Update member's baptism date if not set
            if (empty($member->baptism_date)) {
                $member->baptism_date = $validated['baptism_date'];
            }
            
            // Update member's confirmation date if not set
            if (empty($member->confirmation_date) && !empty($validated['confirmation_date'])) {
                $member->confirmation_date = $validated['confirmation_date'];
            }
            
            $member->save();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Baptism record created successfully',
                'record' => $baptismRecord,
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create baptism record: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Store a new marriage record.
     */
    public function storeMarriageRecord(Request $request)
    {
        $validated = $request->validate([
            'husband_id' => 'nullable|exists:members,id',
            'wife_id' => 'nullable|exists:members,id',
            'record_number' => 'nullable|string|max:50|unique:marriage_records,record_number',
            'husband_name' => 'required|string|max:255',
            'husband_father_name' => 'required|string|max:255',
            'husband_mother_name' => 'required|string|max:255',
            'husband_tribe' => 'nullable|string|max:255',
            'husband_clan' => 'nullable|string|max:255',
            'husband_birth_place' => 'nullable|string|max:255',
            'husband_domicile' => 'nullable|string|max:255',
            'husband_baptized_at' => 'nullable|string|max:255',
            'husband_baptism_date' => 'nullable|date',
            'husband_widower_of' => 'nullable|string|max:255',
            'husband_parent_consent' => 'nullable|boolean',
            'wife_name' => 'required|string|max:255',
            'wife_father_name' => 'required|string|max:255',
            'wife_mother_name' => 'required|string|max:255',
            'wife_tribe' => 'nullable|string|max:255',
            'wife_clan' => 'nullable|string|max:255',
            'wife_birth_place' => 'nullable|string|max:255',
            'wife_domicile' => 'nullable|string|max:255',
            'wife_baptized_at' => 'nullable|string|max:255',
            'wife_baptism_date' => 'nullable|date',
            'wife_widow_of' => 'nullable|string|max:255',
            'wife_parent_consent' => 'nullable|boolean',
            'banas_number' => 'nullable|string|max:50',
            'banas_church_1' => 'nullable|string|max:255',
            'banas_date_1' => 'nullable|date',
            'banas_church_2' => 'nullable|string|max:255',
            'banas_date_2' => 'nullable|date',
            'dispensation_from' => 'nullable|string|max:255',
            'dispensation_given_by' => 'nullable|string|max:255',
            'dispensation_impediment' => 'nullable|string|max:255',
            'dispensation_date' => 'nullable|date',
            'marriage_date' => 'required|date',
            'marriage_church' => 'required|string|max:255',
            'district' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'presence_of' => 'required|string|max:255',
            'delegated_by' => 'nullable|string|max:255',
            'delegation_date' => 'nullable|date',
            'male_witness_name' => 'required|string|max:255',
            'male_witness_father' => 'nullable|string|max:255',
            'male_witness_clan' => 'nullable|string|max:255',
            'female_witness_name' => 'required|string|max:255',
            'female_witness_father' => 'nullable|string|max:255',
            'female_witness_clan' => 'nullable|string|max:255',
            'civil_marriage_certificate_number' => 'nullable|string|max:100',
            'other_documents' => 'nullable|string|max:255',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Create the marriage sacrament record
            $marriageSacrament = new Sacrament([
                'member_id' => $validated['husband_id'] ?? $validated['wife_id'],
                'sacrament_type' => 'marriage',
                'sacrament_date' => $validated['marriage_date'],
                'location' => $validated['marriage_church'],
                'celebrant' => $validated['presence_of'],
                'witness_1' => $validated['male_witness_name'],
                'witness_2' => $validated['female_witness_name'],
                'certificate_number' => $validated['civil_marriage_certificate_number'] ?? null,
                'notes' => $validated['other_documents'] ?? null,
                'recorded_by' => Auth::id(),
            ]);
            
            $marriageSacrament->save();
            
            // Generate a unique record number for the marriage record if not provided
            if (empty($validated['record_number'])) {
                $validated['record_number'] = MarriageRecord::generateRecordNumber();
            }
            
            // Create the detailed marriage record
            $marriageRecord = new MarriageRecord($validated);
            $marriageRecord->sacrament_id = $marriageSacrament->id;
            $marriageRecord->parish_priest_id = Auth::id();
            $marriageRecord->save();
            
            // Link marriage record to sacrament record
            $marriageSacrament->detailed_record_type = MarriageRecord::class;
            $marriageSacrament->detailed_record_id = $marriageRecord->id;
            $marriageSacrament->save();
            
            // Update husband's matrimony status if husband_id is provided
            if (!empty($validated['husband_id'])) {
                $husband = Member::find($validated['husband_id']);
                if ($husband) {
                    $husband->matrimony_status = 'married';
                    $husband->save();
                }
            }
            
            // Update wife's matrimony status if wife_id is provided
            if (!empty($validated['wife_id'])) {
                $wife = Member::find($validated['wife_id']);
                if ($wife) {
                    $wife->matrimony_status = 'married';
                    $wife->save();
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Marriage record created successfully',
                'record' => $marriageRecord,
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create marriage record: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get a baptism record by member ID.
     */
    public function getBaptismRecord($memberId)
    {
        $baptismRecord = BaptismRecord::where('member_id', $memberId)
            ->with(['baptismSacrament', 'eucharistSacrament', 'confirmationSacrament', 'marriageSacrament'])
            ->first();
            
        if (!$baptismRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Baptism record not found for this member',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'record' => $baptismRecord,
        ]);
    }
    
    /**
     * Get a marriage record by husband or wife ID.
     */
    public function getMarriageRecord(Request $request)
    {
        $memberId = $request->member_id;
        
        $marriageRecord = MarriageRecord::where('husband_id', $memberId)
            ->orWhere('wife_id', $memberId)
            ->with(['sacrament'])
            ->first();
            
        if (!$marriageRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Marriage record not found for this member',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'record' => $marriageRecord,
        ]);
    }
}
