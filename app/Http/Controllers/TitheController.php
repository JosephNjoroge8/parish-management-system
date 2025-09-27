<?php
// filepath: app/Http/Controllers/TitheController.php
namespace App\Http\Controllers;

use App\Models\Tithe;
use App\Models\Member;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TitheController extends Controller
{
    public function index(Request $request)
    {
        $query = Tithe::with('member');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('member', function ($memberQuery) use ($search) {
                      $memberQuery->where('first_name', 'like', "%{$search}%")
                                 ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('tithe_type')) {
            $query->where('tithe_type', $request->get('tithe_type'));
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->get('payment_method'));
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('date_given', [
                $request->get('date_from'),
                $request->get('date_to')
            ]);
        }

        if ($request->filled('year')) {
            $query->whereYear('date_given', $request->get('year'));
        }

        if ($request->filled('month')) {
            $query->whereMonth('date_given', $request->get('month'));
        }

        $tithes = $query->orderBy('date_given', 'desc')
                       ->paginate(15)
                       ->withQueryString();

        // Calculate totals
        $totals = [
            'total_amount' => $query->sum('amount'),
            'this_month' => (clone $query)->whereYear('date_given', now()->year)->whereMonth('date_given', now()->month)->sum('amount'),
            'this_year' => (clone $query)->whereYear('date_given', now()->year)->sum('amount'),
            'total_records' => $query->count(),
            'by_type' => Tithe::select('tithe_type', DB::raw('SUM(amount) as total'))
                             ->groupBy('tithe_type')
                             ->pluck('total', 'tithe_type'),
            'by_method' => Tithe::select('payment_method', DB::raw('SUM(amount) as total'))
                             ->groupBy('payment_method')
                             ->pluck('total', 'payment_method'),
        ];

        // Get available years for filter
        $years = Tithe::selectRaw('strftime("%Y", date_given) as year')
                     ->distinct()
                     ->orderBy('year', 'desc')
                     ->pluck('year');

        return Inertia::render('Tithes/Index', [
            'tithing' => $tithes,
            'statistics' => $totals, // <-- match frontend prop name
            'titheTypes' => Tithe::TITHE_TYPES,
            'paymentMethods' => Tithe::PAYMENT_METHODS,
            'years' => $years,
            'filters' => $request->only(['search', 'tithe_type', 'payment_method', 'date_from', 'date_to', 'year', 'month']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Tithes/Create', [
            'titheTypes' => Tithe::TITHE_TYPES,
            'paymentMethods' => Tithe::PAYMENT_METHODS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:1',
            'date_given' => 'required|date',
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string|max:255',
            'tithe_type' => 'required|string',
            'recorded_by' => 'required|string|max:255', // <-- FIXED
            'notes' => 'nullable|string',
        ]);

        \App\Models\Tithe::create($validated);

        return redirect()->route('tithes.index')->with('success', 'Contribution recorded successfully!');
    }

    public function show(Tithe $tithe)
    {
        $tithe->load('member', 'recordedBy');

        return Inertia::render('Tithes/Show', [
            'tithe' => $tithe,
        ]);
    }

    public function edit(Tithe $tithe)
    {
        $tithe->load('member');

        return Inertia::render('Tithes/Edit', [
            'tithe' => $tithe,
            'titheTypes' => Tithe::TITHE_TYPES,
            'paymentMethods' => Tithe::PAYMENT_METHODS,
        ]);
    }

    public function update(Request $request, Tithe $tithe)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'tithe_type' => 'required|in:' . implode(',', array_keys(Tithe::TITHE_TYPES)),
            'payment_method' => 'required|in:' . implode(',', array_keys(Tithe::PAYMENT_METHODS)),
            'date_given' => 'required|date',
            'purpose' => 'nullable|string|max:255',
            'receipt_number' => 'nullable|string|max:100',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $tithe->update($validated);

        return redirect()->route('tithes.index')
            ->with('success', 'Tithe record updated successfully.');
    }

    public function destroy(Tithe $tithe)
    {
        $tithe->delete();

        return redirect()->route('tithes.index')
            ->with('success', 'Tithe record deleted successfully.');
    }

    public function memberTithes(Member $member)
    {
        $tithes = $member->tithes()
                        ->orderBy('date_given', 'desc')
                        ->paginate(10);
        
        $totals = [
            'total_amount' => $member->tithes()->sum('amount'),
            'this_year' => $member->tithes()->whereYear('date_given', now()->year)->sum('amount'),
            'this_month' => $member->tithes()->whereYear('date_given', now()->year)
                                          ->whereMonth('date_given', now()->month)
                                          ->sum('amount'),
        ];

        return Inertia::render('Tithes/MemberTithes', [
            'member' => $member,
            'tithes' => $tithes,
            'totals' => $totals,
        ]);
    }

    public function reports(Request $request)
    {
        $year = $request->get('year', now()->year);
        $month = $request->get('month');

        $query = Tithe::whereYear('date_given', $year);
        
        if ($month) {
            $query->whereMonth('date_given', $month);
        }

        $reports = [
            'total_amount' => $query->sum('amount'),
            'total_records' => $query->count(),
            'by_type' => $query->select('tithe_type', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                              ->groupBy('tithe_type')
                              ->get(),
            'by_method' => $query->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                                ->groupBy('payment_method')
                                ->get(),
            'monthly_totals' => Tithe::whereRaw('CAST(strftime("%Y", date_given) AS INTEGER) = ?', [$year])
                                   ->select(DB::raw('CAST(strftime("%m", date_given) AS INTEGER) as month'), DB::raw('SUM(amount) as total'))
                                   ->groupBy(DB::raw('strftime("%m", date_given)'))
                                   ->orderBy('month')
                                   ->get(),
        ];

        return Inertia::render('Tithes/Reports', [
            'reports' => $reports,
            'year' => $year,
            'month' => $month,
        ]);
    }
}