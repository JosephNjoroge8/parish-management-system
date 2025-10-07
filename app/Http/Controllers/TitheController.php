<?php

// filepath: app/Http/Controllers/TitheController.php

namespace App\Http\Controllers;

use App\Helpers\DatabaseCompatibilityHelper;
use App\Models\Member;
use App\Models\Tithe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

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
            $query->whereBetween('contribution_date', [
                $request->get('date_from'),
                $request->get('date_to'),
            ]);
        }

        if ($request->filled('year')) {
            DatabaseCompatibilityHelper::whereYear($query, 'contribution_date', $request->get('year'));
        }

        if ($request->filled('month')) {
            DatabaseCompatibilityHelper::whereMonth($query, 'contribution_date', $request->get('month'));
        }

        $tithes = $query->orderBy('contribution_date', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Calculate totals
        $totals = [
            'total_amount' => $query->sum('amount'),
            'this_month' => DatabaseCompatibilityHelper::whereYear(
                DatabaseCompatibilityHelper::whereMonth(
                    Tithe::query(), 'contribution_date', now()->month
                ), 'contribution_date', now()->year
            )->sum('amount'),
            'this_year' => DatabaseCompatibilityHelper::whereYear(
                Tithe::query(), 'contribution_date', now()->year
            )->sum('amount'),
            'total_records' => $query->count(),
            'by_type' => Tithe::select('tithe_type', DB::raw('SUM(amount) as total'))
                ->groupBy('tithe_type')
                ->pluck('total', 'tithe_type'),
            'by_method' => Tithe::select('payment_method', DB::raw('SUM(amount) as total'))
                ->groupBy('payment_method')
                ->pluck('total', 'payment_method'),
        ];

        // Get available years for filter
        $years = DatabaseCompatibilityHelper::getDistinctYears(
            Tithe::query(), 'contribution_date'
        );

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
            'contribution_date' => 'required|date',
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
            'tithe_type' => 'required|in:'.implode(',', array_keys(Tithe::TITHE_TYPES)),
            'payment_method' => 'required|in:'.implode(',', array_keys(Tithe::PAYMENT_METHODS)),
            'contribution_date' => 'required|date',
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
            ->orderBy('contribution_date', 'desc')
            ->paginate(10);

        $totals = [
            'total_amount' => $member->tithes()->sum('amount'),
            'this_year' => $member->tithes()->whereYear('contribution_date', now()->year)->sum('amount'),
            'this_month' => $member->tithes()->whereYear('contribution_date', now()->year)
                ->whereMonth('contribution_date', now()->month)
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

        $query = Tithe::whereYear('contribution_date', $year);

        if ($month) {
            $query->whereMonth('contribution_date', $month);
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
            'monthly_totals' => Tithe::whereRaw('YEAR(contribution_date) = ?', [$year])
                ->select(DB::raw('MONTH(contribution_date) as month'), DB::raw('SUM(amount) as total'))
                ->groupBy(DB::raw('MONTH(contribution_date)'))
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
