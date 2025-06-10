<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\Member;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\StoreFamilyRequest;
use App\Http\Requests\UpdateFamilyRequest;

class FamilyController extends Controller
{
    public function index(Request $request)
    {
        $query = Family::with('headOfFamily')->withCount('members');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('family_name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('deanery', 'like', "%{$search}%")
                  ->orWhere('parish', 'like', "%{$search}%");
            });
        }

        if ($request->filled('deanery')) {
            $query->where('deanery', $request->deanery);
        }

        if ($request->filled('parish')) {
            $query->where('parish', $request->parish);
        }

        $families = $query->paginate(15)->withQueryString();

        $deaneries = Family::distinct()->pluck('deanery')->sort()->values();
        $parishes = Family::distinct()->pluck('parish')->sort()->values();

        return Inertia::render('Families/Index', [
            'families' => $families,
            'deaneries' => $deaneries,
            'parishes' => $parishes,
            'filters' => $request->only(['search', 'deanery', 'parish']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Families/Create');
    }

    public function store(StoreFamilyRequest $request)
    {
        Family::create($request->validated());

        return redirect()->route('families.index')
            ->with('success', 'Family created successfully.');
    }

    public function show(Family $family)
    {
        $family->load(['members', 'headOfFamily']);

        return Inertia::render('Families/Show', [
            'family' => $family,
        ]);
    }

    public function edit(Family $family)
    {
        $familyMembers = $family->members()->get(['id', 'first_name', 'last_name']);

        return Inertia::render('Families/Edit', [
            'family' => $family,
            'familyMembers' => $familyMembers,
        ]);
    }

    public function update(UpdateFamilyRequest $request, Family $family)
    {
        $family->update($request->validated());

        return redirect()->route('families.show', $family)
            ->with('success', 'Family updated successfully.');
    }

    public function destroy(Family $family)
    {
        $family->delete();

        return redirect()->route('families.index')
            ->with('success', 'Family deleted successfully.');
    }
}