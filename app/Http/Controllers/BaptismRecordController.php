<?php

namespace App\Http\Controllers;

use App\Models\BaptismRecord;
use App\Models\Member;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class BaptismRecordController extends Controller
{
    public function index()
    {
        $baptismRecords = BaptismRecord::with(['member:id,first_name,middle_name,last_name,date_of_birth,gender'])
            ->orderBy('baptism_date', 'desc')
            ->paginate(50);

        return Inertia::render('BaptismRecords/Index', [
            'baptismRecords' => $baptismRecords,
            'filters' => $this->getAvailableFilters()
        ]);
    }

    public function create()
    {
        return Inertia::render('BaptismRecords/Create', [
            'members' => Member::select('id', 'first_name', 'middle_name', 'last_name', 'date_of_birth')
                ->orderBy('last_name')
                ->get(),
            'ministers' => $this->getAvailableClergy()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'baptism_date' => 'required|date',
            'minister' => 'required|string|max:255',
            'place_of_baptism' => 'nullable|string|max:255',
            'godfather_name' => 'nullable|string|max:255',
            'godmother_name' => 'nullable|string|max:255',
            'godfather_religion' => 'nullable|string|max:255',
            'godmother_religion' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
            'certificate_number' => 'nullable|string|max:100|unique:baptism_records',
        ]);

        DB::transaction(function () use ($validated) {
            // Create baptism record
            $baptismRecord = BaptismRecord::create($validated);

            // Create corresponding sacrament record
            \App\Models\Sacrament::create([
                'member_id' => $validated['member_id'],
                'sacrament_type' => 'baptism',
                'sacrament_date' => $validated['baptism_date'],
                'administered_by' => $validated['minister'],
                'location' => $validated['place_of_baptism'] ?? '',
                'certificate_number' => $validated['certificate_number'],
                'notes' => $validated['remarks'],
            ]);
        });

        return redirect()->route('baptism-records.index')
            ->with('success', 'Baptism record created successfully.');
    }

    public function show(BaptismRecord $baptismRecord)
    {
        $baptismRecord->load('member');
        
        return Inertia::render('BaptismRecords/Show', [
            'baptismRecord' => $baptismRecord
        ]);
    }

    public function edit(BaptismRecord $baptismRecord)
    {
        $baptismRecord->load('member');
        
        return Inertia::render('BaptismRecords/Edit', [
            'baptismRecord' => $baptismRecord,
            'members' => Member::select('id', 'first_name', 'middle_name', 'last_name', 'date_of_birth')
                ->orderBy('last_name')
                ->get(),
            'ministers' => $this->getAvailableClergy()
        ]);
    }

    public function update(Request $request, BaptismRecord $baptismRecord)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'baptism_date' => 'required|date',
            'minister' => 'required|string|max:255',
            'place_of_baptism' => 'nullable|string|max:255',
            'godfather_name' => 'nullable|string|max:255',
            'godmother_name' => 'nullable|string|max:255',
            'godfather_religion' => 'nullable|string|max:255',
            'godmother_religion' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
            'certificate_number' => 'nullable|string|max:100|unique:baptism_records,certificate_number,' . $baptismRecord->id,
        ]);

        DB::transaction(function () use ($baptismRecord, $validated) {
            // Update baptism record
            $baptismRecord->update($validated);

            // Update corresponding sacrament record
            $sacrament = \App\Models\Sacrament::where('member_id', $validated['member_id'])
                ->where('sacrament_type', 'baptism')
                ->first();

            if ($sacrament) {
                $sacrament->update([
                    'sacrament_date' => $validated['baptism_date'],
                    'administered_by' => $validated['minister'],
                    'location' => $validated['place_of_baptism'] ?? '',
                    'certificate_number' => $validated['certificate_number'],
                    'notes' => $validated['remarks'],
                ]);
            }
        });

        return redirect()->route('baptism-records.index')
            ->with('success', 'Baptism record updated successfully.');
    }

    public function destroy(BaptismRecord $baptismRecord)
    {
        DB::transaction(function () use ($baptismRecord) {
            // Delete corresponding sacrament record
            \App\Models\Sacrament::where('member_id', $baptismRecord->member_id)
                ->where('sacrament_type', 'baptism')
                ->delete();

            // Delete baptism record
            $baptismRecord->delete();
        });

        return redirect()->route('baptism-records.index')
            ->with('success', 'Baptism record deleted successfully.');
    }

    /**
     * Filter baptism records based on request parameters
     */
    public function filter(Request $request)
    {
        $query = BaptismRecord::with(['member:id,first_name,middle_name,last_name,date_of_birth,gender']);

        // Apply filters
        if ($request->filled('member_name')) {
            $query->whereHas('member', function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->member_name . '%')
                  ->orWhere('last_name', 'like', '%' . $request->member_name . '%');
            });
        }

        if ($request->filled('minister')) {
            $query->where('minister', 'like', '%' . $request->minister . '%');
        }

        if ($request->filled('date_from')) {
            $query->where('baptism_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('baptism_date', '<=', $request->date_to);
        }

        if ($request->filled('place_of_baptism')) {
            $query->where('place_of_baptism', 'like', '%' . $request->place_of_baptism . '%');
        }

        $baptismRecords = $query->orderBy('baptism_date', 'desc')->paginate(50);

        return response()->json([
            'baptismRecords' => $baptismRecords,
            'success' => true
        ]);
    }

    /**
     * Generate baptism certificate PDF
     */
    public function generateCertificate(BaptismRecord $baptismRecord)
    {
        try {
            $baptismRecord->load('member');
            
            // Prepare data for certificate
            $data = [
                'baptismRecord' => $baptismRecord,
                'member' => $baptismRecord->member,
                'parish_name' => config('app.parish_name', 'Sacred Heart Kandara Parish'),
                'generated_at' => now()
            ];
            
            // Generate PDF using Dompdf
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('certificates.baptism-card', $data);
            $pdf->setPaper('A4', 'portrait');
            
            $filename = 'baptism-certificate-' . $baptismRecord->member->first_name . '-' . $baptismRecord->member->last_name . '-' . now()->format('Y-m-d') . '.pdf';
            
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate certificate: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download baptism certificate for a member by ID
     */
    public function downloadBaptismCertificate($memberId)
    {
        try {
            $member = Member::findOrFail($memberId);
            
            // Get baptism record for this member
            $baptismRecord = BaptismRecord::where('member_id', $memberId)->first();
            
            if (!$baptismRecord) {
                // Create a basic baptism record from member data if none exists
                $baptismRecord = new BaptismRecord([
                    'member_id' => $member->id,
                    'baptism_date' => $member->baptism_date ?? now(),
                    'minister' => 'Parish Priest',
                    'place_of_baptism' => $member->local_church ?? 'Sacred Heart Kandara Parish',
                ]);
            }
            
            // Prepare comprehensive data for certificate
            $data = [
                'baptismRecord' => $baptismRecord,
                'member' => $member,
                'parish_name' => config('app.parish_name', 'Sacred Heart Kandara Parish'),
                'generated_at' => now()
            ];
            
            // Generate PDF using Dompdf
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('certificates.baptism-card', $data);
            $pdf->setPaper('A4', 'portrait');
            
            $filename = 'baptism-certificate-' . $member->first_name . '-' . $member->last_name . '-' . now()->format('Y-m-d') . '.pdf';
            
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate baptism certificate: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics for baptism records
     */
    public function statistics()
    {
        $stats = [
            'total_baptisms' => BaptismRecord::count(),
            'this_year' => BaptismRecord::whereYear('baptism_date', now()->year)->count(),
            'this_month' => BaptismRecord::whereYear('baptism_date', now()->year)
                ->whereMonth('baptism_date', now()->month)->count(),
            'by_month' => BaptismRecord::selectRaw('CAST(strftime("%m", baptism_date) AS INTEGER) as month, COUNT(*) as count')
                ->whereYear('baptism_date', now()->year)
                ->groupBy('month')
                ->orderBy('month')
                ->get(),
            'by_minister' => BaptismRecord::selectRaw('minister, COUNT(*) as count')
                ->groupBy('minister')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
        ];

        return response()->json($stats);
    }

    private function getAvailableFilters()
    {
        return [
            'ministers' => BaptismRecord::select('minister')
                ->distinct()
                ->whereNotNull('minister')
                ->orderBy('minister')
                ->pluck('minister')
                ->toArray(),
            'places' => BaptismRecord::select('place_of_baptism')
                ->distinct()
                ->whereNotNull('place_of_baptism')
                ->orderBy('place_of_baptism')
                ->pluck('place_of_baptism')
                ->toArray(),
        ];
    }

    private function getAvailableClergy()
    {
        // This could be expanded to fetch from a clergy table
        return [
            'Fr. John Doe',
            'Fr. Peter Smith',
            'Fr. Michael Johnson',
            'Bishop Thomas Wilson',
            'Deacon Paul Brown'
        ];
    }

    /**
     * Bulk import baptism records from CSV/Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx,xls|max:2048'
        ]);

        // Implementation for bulk import would go here
        return response()->json([
            'success' => true,
            'message' => 'Bulk import functionality to be implemented'
        ]);
    }

    /**
     * Export baptism records to Excel/PDF
     */
    public function export(Request $request)
    {
        $format = $request->input('format', 'excel');
        $filters = $request->input('filters', []);

        // Apply filters and export
        $query = BaptismRecord::with('member');
        
        // Apply same filters as in filter method
        // ... filter logic here ...

        $filename = 'baptism-records-' . now()->format('Y-m-d');

        // Implementation for export would use Excel package
        return response()->json([
            'success' => true,
            'message' => 'Export functionality to be implemented',
            'filename' => $filename
        ]);
    }
}
