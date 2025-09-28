<?php

namespace App\Http\Controllers;

use App\Models\MarriageRecord;
use App\Models\Member;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class MarriageRecordController extends Controller
{
    public function index()
    {
        $marriageRecords = MarriageRecord::with(['husband:id,first_name,middle_name,last_name', 'wife:id,first_name,middle_name,last_name'])
            ->orderBy('marriage_date', 'desc')
            ->paginate(50);

        return Inertia::render('MarriageRecords/Index', [
            'marriageRecords' => $marriageRecords,
            'filters' => $this->getAvailableFilters()
        ]);
    }

    public function create()
    {
        return Inertia::render('MarriageRecords/Create', [
            'members' => Member::select('id', 'first_name', 'middle_name', 'last_name', 'date_of_birth', 'gender')
                ->orderBy('last_name')
                ->get(),
            'ministers' => $this->getAvailableClergy()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'husband_id' => 'nullable|exists:members,id',
            'wife_id' => 'nullable|exists:members,id',
            'husband_name' => 'required|string|max:255',
            'wife_name' => 'required|string|max:255',
            'husband_father_name' => 'nullable|string|max:255',
            'husband_mother_name' => 'nullable|string|max:255',
            'wife_father_name' => 'nullable|string|max:255',
            'wife_mother_name' => 'nullable|string|max:255',
            'husband_birth_date' => 'nullable|date',
            'wife_birth_date' => 'nullable|date',
            'husband_birth_place' => 'nullable|string|max:255',
            'wife_birth_place' => 'nullable|string|max:255',
            'husband_residence' => 'nullable|string|max:255',
            'wife_residence' => 'nullable|string|max:255',
            'husband_tribe' => 'nullable|string|max:255',
            'wife_tribe' => 'nullable|string|max:255',
            'husband_baptism_parish' => 'nullable|string|max:255',
            'wife_baptism_parish' => 'nullable|string|max:255',
            'marriage_date' => 'required|date',
            'marriage_location' => 'required|string|max:255',
            'officiant_name' => 'required|string|max:255',
            'witness1_name' => 'nullable|string|max:255',
            'witness2_name' => 'nullable|string|max:255',
            'register_volume' => 'nullable|string|max:100',
            'register_number' => 'nullable|string|max:100',
            'page_number' => 'nullable|string|max:100',
            'certificate_number' => 'nullable|string|max:100|unique:marriage_records',
            'remarks' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            // Create marriage record
            $marriageRecord = MarriageRecord::create($validated);

            // Create corresponding sacrament records for both spouses if they are members
            if ($validated['husband_id']) {
                \App\Models\Sacrament::create([
                    'member_id' => $validated['husband_id'],
                    'sacrament_type' => 'marriage',
                    'sacrament_date' => $validated['marriage_date'],
                    'administered_by' => $validated['officiant_name'],
                    'location' => $validated['marriage_location'],
                    'certificate_number' => $validated['certificate_number'],
                    'notes' => $validated['remarks'],
                ]);
            }

            if ($validated['wife_id']) {
                \App\Models\Sacrament::create([
                    'member_id' => $validated['wife_id'],
                    'sacrament_type' => 'marriage',
                    'sacrament_date' => $validated['marriage_date'],
                    'administered_by' => $validated['officiant_name'],
                    'location' => $validated['marriage_location'],
                    'certificate_number' => $validated['certificate_number'],
                    'notes' => $validated['remarks'],
                ]);
            }
        });

        return redirect()->route('marriage-records.index')
            ->with('success', 'Marriage record created successfully.');
    }

    public function show(MarriageRecord $marriageRecord)
    {
        $marriageRecord->load(['husband', 'wife']);
        
        return Inertia::render('MarriageRecords/Show', [
            'marriageRecord' => $marriageRecord
        ]);
    }

    public function edit(MarriageRecord $marriageRecord)
    {
        $marriageRecord->load(['husband', 'wife']);
        
        return Inertia::render('MarriageRecords/Edit', [
            'marriageRecord' => $marriageRecord,
            'members' => Member::select('id', 'first_name', 'middle_name', 'last_name', 'date_of_birth', 'gender')
                ->orderBy('last_name')
                ->get(),
            'ministers' => $this->getAvailableClergy()
        ]);
    }

    public function update(Request $request, MarriageRecord $marriageRecord)
    {
        $validated = $request->validate([
            'husband_id' => 'nullable|exists:members,id',
            'wife_id' => 'nullable|exists:members,id',
            'husband_name' => 'required|string|max:255',
            'wife_name' => 'required|string|max:255',
            'husband_father_name' => 'nullable|string|max:255',
            'husband_mother_name' => 'nullable|string|max:255',
            'wife_father_name' => 'nullable|string|max:255',
            'wife_mother_name' => 'nullable|string|max:255',
            'husband_birth_date' => 'nullable|date',
            'wife_birth_date' => 'nullable|date',
            'husband_birth_place' => 'nullable|string|max:255',
            'wife_birth_place' => 'nullable|string|max:255',
            'husband_residence' => 'nullable|string|max:255',
            'wife_residence' => 'nullable|string|max:255',
            'husband_tribe' => 'nullable|string|max:255',
            'wife_tribe' => 'nullable|string|max:255',
            'husband_baptism_parish' => 'nullable|string|max:255',
            'wife_baptism_parish' => 'nullable|string|max:255',
            'marriage_date' => 'required|date',
            'marriage_location' => 'required|string|max:255',
            'officiant_name' => 'required|string|max:255',
            'witness1_name' => 'nullable|string|max:255',
            'witness2_name' => 'nullable|string|max:255',
            'register_volume' => 'nullable|string|max:100',
            'register_number' => 'nullable|string|max:100',
            'page_number' => 'nullable|string|max:100',
            'certificate_number' => 'nullable|string|max:100|unique:marriage_records,certificate_number,' . $marriageRecord->id,
            'remarks' => 'nullable|string',
        ]);

        DB::transaction(function () use ($marriageRecord, $validated) {
            // Update marriage record
            $marriageRecord->update($validated);

            // Update corresponding sacrament records
            if ($validated['husband_id']) {
                $sacrament = \App\Models\Sacrament::where('member_id', $validated['husband_id'])
                    ->where('sacrament_type', 'marriage')
                    ->first();

                if ($sacrament) {
                    $sacrament->update([
                        'sacrament_date' => $validated['marriage_date'],
                        'administered_by' => $validated['officiant_name'],
                        'location' => $validated['marriage_location'],
                        'certificate_number' => $validated['certificate_number'],
                        'notes' => $validated['remarks'],
                    ]);
                }
            }

            if ($validated['wife_id']) {
                $sacrament = \App\Models\Sacrament::where('member_id', $validated['wife_id'])
                    ->where('sacrament_type', 'marriage')
                    ->first();

                if ($sacrament) {
                    $sacrament->update([
                        'sacrament_date' => $validated['marriage_date'],
                        'administered_by' => $validated['officiant_name'],
                        'location' => $validated['marriage_location'],
                        'certificate_number' => $validated['certificate_number'],
                        'notes' => $validated['remarks'],
                    ]);
                }
            }
        });

        return redirect()->route('marriage-records.index')
            ->with('success', 'Marriage record updated successfully.');
    }

    public function destroy(MarriageRecord $marriageRecord)
    {
        DB::transaction(function () use ($marriageRecord) {
            // Delete corresponding sacrament records
            if ($marriageRecord->husband_id) {
                \App\Models\Sacrament::where('member_id', $marriageRecord->husband_id)
                    ->where('sacrament_type', 'marriage')
                    ->delete();
            }

            if ($marriageRecord->wife_id) {
                \App\Models\Sacrament::where('member_id', $marriageRecord->wife_id)
                    ->where('sacrament_type', 'marriage')
                    ->delete();
            }

            // Delete marriage record
            $marriageRecord->delete();
        });

        return redirect()->route('marriage-records.index')
            ->with('success', 'Marriage record deleted successfully.');
    }

    /**
     * Generate marriage certificate PDF
     */
    public function generateCertificate(MarriageRecord $marriageRecord)
    {
        try {
            $marriageRecord->load(['husband', 'wife']);
            
            // Enhance marriage record with additional computed fields for template
            $enhancedRecord = $this->enhanceMarriageRecordForCertificate($marriageRecord);
            
            // Prepare data for certificate
            $data = [
                'marriageRecord' => $enhancedRecord,
                'parish_name' => config('app.parish_name', 'Sacred Heart Kandara Parish'),
                'generated_at' => now()
            ];
            
            // Generate PDF using Dompdf
            $pdf = Pdf::loadView('certificates.marriage-certificate', $data);
            $pdf->setPaper('A4', 'portrait');
            
            $husbandName = Str::slug($marriageRecord->husband_name ?: 'unknown');
            $wifeName = Str::slug($marriageRecord->wife_name ?: 'unknown');
            $filename = 'marriage-certificate-' . $husbandName . '-' . $wifeName . '-' . now()->format('Y-m-d') . '.pdf';
            
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate marriage certificate: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download marriage certificate by marriage record ID
     */
    public function downloadMarriageCertificate($marriageRecordId)
    {
        try {
            $marriageRecord = MarriageRecord::with(['husband', 'wife'])->findOrFail($marriageRecordId);
            
            // Enhance marriage record with additional computed fields for template
            $enhancedRecord = $this->enhanceMarriageRecordForCertificate($marriageRecord);
            
            // Prepare comprehensive data for certificate
            $data = [
                'marriageRecord' => $enhancedRecord,
                'parish_name' => config('app.parish_name', 'Sacred Heart Kandara Parish'),
                'generated_at' => now()
            ];
            
            // Generate PDF using Dompdf
            $pdf = Pdf::loadView('certificates.marriage-certificate', $data);
            $pdf->setPaper('A4', 'portrait');
            
            $husbandName = Str::slug($marriageRecord->husband_name ?: 'unknown');
            $wifeName = Str::slug($marriageRecord->wife_name ?: 'unknown');
            $filename = 'marriage-certificate-' . $husbandName . '-' . $wifeName . '-' . now()->format('Y-m-d') . '.pdf';
            
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate marriage certificate: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Find marriage certificate for a member
     */
    public function findMemberMarriageCertificate($memberId)
    {
        try {
            $member = Member::findOrFail($memberId);
            
            // Find marriage record where this member is either husband or wife
            $marriageRecord = MarriageRecord::where('husband_id', $memberId)
                ->orWhere('wife_id', $memberId)
                ->with(['husband', 'wife'])
                ->first();
            
            if (!$marriageRecord && $member->matrimony_status === 'married') {
                // Create a basic marriage record from member data if none exists but member is married
                $marriageRecord = $this->createBasicMarriageRecordFromMember($member);
            }
            
            if (!$marriageRecord) {
                return response()->json([
                    'error' => 'No marriage record found for this member and member is not marked as married'
                ], 404);
            }
            
            return $this->downloadMarriageCertificate($marriageRecord->id);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to find marriage certificate: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enhance marriage record with computed fields needed for certificate template
     */
    private function enhanceMarriageRecordForCertificate(MarriageRecord $marriageRecord)
    {
        // Create a copy of the marriage record as array to add computed fields
        $enhanced = $marriageRecord->toArray();
        
        // Add missing fields that our template expects
        $enhanced['entry_number'] = $marriageRecord->record_number ?? str_pad($marriageRecord->id, 4, '0', STR_PAD_LEFT);
        $enhanced['sub_county'] = $marriageRecord->district ?? '';
        $enhanced['county'] = $marriageRecord->province ?? '';
        $enhanced['marriage_location'] = $marriageRecord->marriage_church ?? '';
        $enhanced['venue'] = $marriageRecord->marriage_church ?? '';
        $enhanced['religion'] = 'Catholic';
        $enhanced['license_number'] = $marriageRecord->civil_marriage_certificate_number ?? '';
        $enhanced['officiant_name'] = $marriageRecord->presence_of ?? '';
        
        // Enhance husband details
        if ($marriageRecord->husband) {
            $husband = $marriageRecord->husband;
            $enhanced['husband_age'] = $husband->date_of_birth ? 
                \Carbon\Carbon::parse($husband->date_of_birth)->age : '';
            $enhanced['husband_residence'] = $husband->address ?? $marriageRecord->husband_domicile ?? '';
            $enhanced['husband_county'] = $husband->county ?? $marriageRecord->province ?? '';
            $enhanced['husband_occupation'] = $husband->occupation ?? '';
            $enhanced['husband_marital_status'] = 'Single'; // Default for first marriage
            
            // Parent details from member if not in marriage record
            $enhanced['husband_father_name'] = $marriageRecord->husband_father_name ?? $husband->father_name ?? '';
            $enhanced['husband_mother_name'] = $marriageRecord->husband_mother_name ?? $husband->mother_name ?? '';
            $enhanced['husband_father_occupation'] = $husband->father_occupation ?? '';
            $enhanced['husband_mother_occupation'] = $husband->mother_occupation ?? '';
            $enhanced['husband_father_residence'] = $husband->father_residence ?? '';
            $enhanced['husband_mother_residence'] = $husband->mother_residence ?? '';
        }
        
        // Enhance wife details
        if ($marriageRecord->wife) {
            $wife = $marriageRecord->wife;
            $enhanced['wife_age'] = $wife->date_of_birth ? 
                \Carbon\Carbon::parse($wife->date_of_birth)->age : '';
            $enhanced['wife_residence'] = $wife->address ?? $marriageRecord->wife_domicile ?? '';
            $enhanced['wife_county'] = $wife->county ?? $marriageRecord->province ?? '';
            $enhanced['wife_occupation'] = $wife->occupation ?? '';
            $enhanced['wife_marital_status'] = 'Single'; // Default for first marriage
            
            // Parent details from member if not in marriage record
            $enhanced['wife_father_name'] = $marriageRecord->wife_father_name ?? $wife->father_name ?? '';
            $enhanced['wife_mother_name'] = $marriageRecord->wife_mother_name ?? $wife->mother_name ?? '';
            $enhanced['wife_father_occupation'] = $wife->father_occupation ?? '';
            $enhanced['wife_mother_occupation'] = $wife->mother_occupation ?? '';
            $enhanced['wife_father_residence'] = $wife->father_residence ?? '';
            $enhanced['wife_mother_residence'] = $wife->mother_residence ?? '';
        }
        
        // Witness information
        $enhanced['witness1_name'] = $marriageRecord->male_witness_full_name ?? '';
        $enhanced['witness2_name'] = $marriageRecord->female_witness_full_name ?? '';
        
        // Convert back to object for template compatibility
        return (object) $enhanced;
    }

    /**
     * Create a basic marriage record from member data if none exists
     */
    private function createBasicMarriageRecordFromMember(Member $member)
    {
        // This is a fallback method - in production, proper marriage records should be entered
        $currentDate = now();
        
        // Load relationships to avoid N+1 queries
        $member->load('parent');
        
        // Get parent name safely
        $parentName = $member->parent ? 
            trim($member->parent->first_name . ' ' . $member->parent->last_name) : 
            'Unknown';
        
        // Check gender case-insensitively
        $isMale = strtolower($member->gender) === 'male';
        $isFemale = strtolower($member->gender) === 'female';
        
        $marriageRecord = MarriageRecord::create([
            'record_number' => 'AUTO-' . str_pad($member->id, 4, '0', STR_PAD_LEFT),
            
            // Husband Information (required fields)
            'husband_id' => $isMale ? $member->id : null,
            'husband_name' => $isMale ? 
                trim($member->first_name . ' ' . ($member->middle_name ?? '') . ' ' . $member->last_name) : 
                'Not Applicable',
            'husband_father_name' => $isMale ? $parentName : 'Not Applicable',
            'husband_mother_name' => $isMale ? 'To Be Provided' : 'Not Applicable',
            'husband_tribe' => $isMale ? ($member->tribe ?? 'Not Specified') : 'Not Applicable',
            'husband_clan' => $isMale ? ($member->clan ?? 'Not Specified') : 'Not Applicable',
            'husband_birth_place' => $isMale ? ($member->residence ?? 'Not Specified') : 'Not Applicable',
            'husband_domicile' => $isMale ? ($member->residence ?? 'Not Specified') : 'Not Applicable',
            'husband_baptized_at' => $isMale ? ($member->local_church ?? config('app.parish_name', 'Sacred Heart Kandara Parish')) : 'Not Applicable',
            'husband_baptism_date' => $isMale ? ($member->baptism_date ?? $currentDate) : $currentDate,
            
            // Wife Information (required fields)
            'wife_id' => $isFemale ? $member->id : null,
            'wife_name' => $isFemale ? 
                trim($member->first_name . ' ' . ($member->middle_name ?? '') . ' ' . $member->last_name) : 
                'Not Applicable',
            'wife_father_name' => $isFemale ? $parentName : 'Not Applicable',
            'wife_mother_name' => $isFemale ? 'To Be Provided' : 'Not Applicable',
            'wife_tribe' => $isFemale ? ($member->tribe ?? 'Not Specified') : 'Not Applicable',
            'wife_clan' => $isFemale ? ($member->clan ?? 'Not Specified') : 'Not Applicable',
            'wife_birth_place' => $isFemale ? ($member->residence ?? 'Not Specified') : 'Not Applicable',
            'wife_domicile' => $isFemale ? ($member->residence ?? 'Not Specified') : 'Not Applicable',
            'wife_baptized_at' => $isFemale ? ($member->local_church ?? config('app.parish_name', 'Sacred Heart Kandara Parish')) : 'Not Applicable',
            'wife_baptism_date' => $isFemale ? ($member->baptism_date ?? $currentDate) : $currentDate,
            
            // Marriage Contract Information (required fields)
            'marriage_date' => $currentDate,
            'marriage_month' => $currentDate->format('F'),
            'marriage_year' => $currentDate->format('Y'),
            'marriage_church' => $member->local_church ?? config('app.parish_name', 'Sacred Heart Kandara Parish'),
            'district' => 'Kandara',
            'province' => 'Murang\'a',
            'presence_of' => 'Parish Priest',
            
            // Witness Information (required fields)
            'male_witness_full_name' => 'To Be Provided',
            'male_witness_father' => 'Unknown',
            'male_witness_clan' => 'Unknown',
            'female_witness_full_name' => 'To Be Provided', 
            'female_witness_father' => 'Unknown',
            'female_witness_clan' => 'Unknown',
        ]);
        
        return $marriageRecord->load(['husband', 'wife']);
    }

    /**
     * Get statistics for marriage records
     */
    public function statistics()
    {
        $stats = [
            'total_marriages' => MarriageRecord::count(),
            'this_year' => MarriageRecord::whereYear('marriage_date', now()->year)->count(),
            'this_month' => MarriageRecord::whereYear('marriage_date', now()->year)
                ->whereMonth('marriage_date', now()->month)->count(),
            'by_month' => MarriageRecord::selectRaw('CAST(strftime("%m", marriage_date) AS INTEGER) as month, COUNT(*) as count')
                ->whereYear('marriage_date', now()->year)
                ->groupBy('month')
                ->orderBy('month')
                ->get(),
            'by_officiant' => MarriageRecord::selectRaw('officiant_name, COUNT(*) as count')
                ->groupBy('officiant_name')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
            'by_location' => MarriageRecord::selectRaw('marriage_location, COUNT(*) as count')
                ->groupBy('marriage_location')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Filter marriage records based on request parameters
     */
    public function filter(Request $request)
    {
        $query = MarriageRecord::with(['husband:id,first_name,middle_name,last_name', 'wife:id,first_name,middle_name,last_name']);

        // Apply filters
        if ($request->filled('husband_name')) {
            $query->where('husband_name', 'like', '%' . $request->husband_name . '%');
        }

        if ($request->filled('wife_name')) {
            $query->where('wife_name', 'like', '%' . $request->wife_name . '%');
        }

        if ($request->filled('officiant')) {
            $query->where('officiant_name', 'like', '%' . $request->officiant . '%');
        }

        if ($request->filled('date_from')) {
            $query->where('marriage_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('marriage_date', '<=', $request->date_to);
        }

        if ($request->filled('marriage_location')) {
            $query->where('marriage_location', 'like', '%' . $request->marriage_location . '%');
        }

        $marriageRecords = $query->orderBy('marriage_date', 'desc')->paginate(50);

        return response()->json([
            'marriageRecords' => $marriageRecords,
            'success' => true
        ]);
    }

    private function getAvailableFilters()
    {
        return [
            'officiants' => MarriageRecord::select('officiant_name')
                ->distinct()
                ->whereNotNull('officiant_name')
                ->orderBy('officiant_name')
                ->pluck('officiant_name')
                ->toArray(),
            'locations' => MarriageRecord::select('marriage_location')
                ->distinct()
                ->whereNotNull('marriage_location')
                ->orderBy('marriage_location')
                ->pluck('marriage_location')
                ->toArray(),
        ];
    }

    private function getAvailableClergy()
    {
        return [
            'Fr. John Doe',
            'Fr. Peter Smith',
            'Fr. Michael Johnson',
            'Bishop Thomas Wilson',
            'Deacon Paul Brown'
        ];
    }

    /**
     * Export marriage records to Excel/PDF
     */
    public function export(Request $request)
    {
        $format = $request->input('format', 'excel');
        $filters = $request->input('filters', []);

        // Apply filters and export
        $query = MarriageRecord::with(['husband', 'wife']);
        
        // Apply same filters as in filter method
        // ... filter logic here ...

        $filename = 'marriage-records-' . now()->format('Y-m-d');

        // Implementation for export would use Excel package
        return response()->json([
            'success' => true,
            'message' => 'Export functionality to be implemented',
            'filename' => $filename
        ]);
    }
}