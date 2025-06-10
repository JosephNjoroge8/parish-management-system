<?php

// app/Http/Controllers/SacramentController.php
namespace App\Http\Controllers;

use App\Models\Sacrament;
use App\Models\Member;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\StoreSacramentRequest;
use App\Http\Requests\UpdateSacramentRequest;

class SacramentController extends Controller
{
    public function index(Request $request)
    {
        $query = Sacrament::with('member');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('member', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            })->orWhere('officiant', 'like', "%{$search}%")
              ->orWhere('location', 'like', "%{$search}%");
        }

        if ($request->filled('sacrament_type')) {
            $query->where('sacrament_type', $request->sacrament_type);
        }

        if ($request->filled('date_from')) {
            $query->where('sacrament_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('sacrament_date', '<=', $request->date_to);
        }

        $sacraments = $query->orderBy('sacrament_date', 'desc')
            ->paginate(15)
            ->withQueryString();

        $sacramentTypes = [
            'baptism' => 'Baptism',
            'confirmation' => 'Confirmation',
            'first_communion' => 'First Communion',
            'matrimony' => 'Matrimony',
            'holy_orders' => 'Holy Orders',
            'anointing_of_sick' => 'Anointing of the Sick',
        ];

        return Inertia::render('Sacraments/Index', [
            'sacraments' => $sacraments,
            'sacramentTypes' => $sacramentTypes,
            'filters' => $request->only(['search', 'sacrament_type', 'date_from', 'date_to']),
        ]);
    }

    public function create()
    {
        $members = Member::active()
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $sacramentTypes = [
            'baptism' => 'Baptism',
            'confirmation' => 'Confirmation',
            'first_communion' => 'First Communion',
            'matrimony' => 'Matrimony',
            'holy_orders' => 'Holy Orders',
            'anointing_of_sick' => 'Anointing of the Sick',
        ];

        return Inertia::render('Sacraments/Create', [
            'members' => $members,
            'sacramentTypes' => $sacramentTypes,
        ]);
    }

    public function store(StoreSacramentRequest $request)
    {
        Sacrament::create($request->validated());

        return redirect()->route('sacraments.index')
            ->with('success', 'Sacrament record created successfully.');
    }

    public function show(Sacrament $sacrament)
    {
        $sacrament->load('member');

        return Inertia::render('Sacraments/Show', [
            'sacrament' => $sacrament,
        ]);
    }

    public function edit(Sacrament $sacrament)
    {
        $members = Member::active()
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $sacramentTypes = [
            'baptism' => 'Baptism',
            'confirmation' => 'Confirmation',
            'first_communion' => 'First Communion',
            'matrimony' => 'Matrimony',
            'holy_orders' => 'Holy Orders',
            'anointing_of_sick' => 'Anointing of the Sick',
        ];

        return Inertia::render('Sacraments/Edit', [
            'sacrament' => $sacrament,
            'members' => $members,
            'sacramentTypes' => $sacramentTypes,
        ]);
    }

    public function update(UpdateSacramentRequest $request, Sacrament $sacrament)
    {
        $sacrament->update($request->validated());

        return redirect()->route('sacraments.show', $sacrament)
            ->with('success', 'Sacrament record updated successfully.');
    }

    public function destroy(Sacrament $sacrament)
    {
        $sacrament->delete();

        return redirect()->route('sacraments.index')
            ->with('success', 'Sacrament record deleted successfully.');
    }
}