<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Member;
use App\Models\CommunityGroup;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ActivityController extends Controller
{
    /**
     * Display a listing of activities
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            
            $query = Activity::with(['communityGroup', 'participants'])
                ->when($request->search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('title', 'like', "%{$search}%")
                          ->orWhere('description', 'like', "%{$search}%")
                          ->orWhere('location', 'like', "%{$search}%")
                          ->orWhere('organizer', 'like', "%{$search}%");
                    });
                })
                ->when($request->activity_type, function ($query, $type) {
                    $query->where('activity_type', $type);
                })
                ->when($request->status, function ($query, $status) {
                    $query->where('status', $status);
                })
                ->when($request->date_from, function ($query, $date) {
                    $query->where('start_date', '>=', $date);
                })
                ->when($request->date_to, function ($query, $date) {
                    $query->where('start_date', '<=', $date);
                })
                ->orderBy('start_date', 'desc');

            $activities = $query->paginate($perPage)->appends($request->query());

            // Get statistics for the dashboard cards
            $statistics = [
                'total_activities' => Activity::count(),
                'upcoming_activities' => Activity::upcoming()->count(),
                'active_activities' => Activity::where('status', 'active')->count(),
                'this_month_activities' => Activity::thisMonth()->count(),
            ];

            // Get activity types and statuses for filters
            $activityTypes = Activity::ACTIVITY_TYPES;
            $statuses = Activity::STATUSES;

            // Get community groups for filters
            $communityGroups = CommunityGroup::select('id', 'name')->orderBy('name')->get();

            return Inertia::render('Activities/Index', [
                'activities' => $activities,
                'statistics' => $statistics,
                'activityTypes' => $activityTypes,
                'statuses' => $statuses,
                'communityGroups' => $communityGroups,
                'filters' => $request->only(['search', 'activity_type', 'status', 'date_from', 'date_to'])
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load activities index: ' . $e->getMessage());
            return back()->with('error', 'Failed to load activities. Please try again.');
        }
    }

    /**
     * Show the form for creating a new activity
     */
    public function create()
    {
        $communityGroups = CommunityGroup::select('id', 'name')->orderBy('name')->get();
        
        return Inertia::render('Activities/Create', [
            'activityTypes' => Activity::ACTIVITY_TYPES,
            'statuses' => Activity::STATUSES,
            'communityGroups' => $communityGroups,
        ]);
    }

    /**
     * Store a newly created activity
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'activity_type' => 'required|string|in:' . implode(',', array_keys(Activity::ACTIVITY_TYPES)),
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'start_time' => 'nullable|date_format:H:i',
                'end_time' => 'nullable|date_format:H:i|after:start_time',
                'location' => 'nullable|string|max:255',
                'organizer' => 'nullable|string|max:255',
                'community_group_id' => 'nullable|exists:community_groups,id',
                'max_participants' => 'nullable|integer|min:1',
                'registration_required' => 'boolean',
                'registration_deadline' => 'nullable|date|before_or_equal:start_date',
                'status' => 'required|string|in:' . implode(',', array_keys(Activity::STATUSES)),
                'notes' => 'nullable|string',
            ]);

            $activity = Activity::create($validated);

            return redirect()->route('activities.index')
                ->with('success', 'Activity created successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to create activity: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Failed to create activity. Please try again.');
        }
    }

    /**
     * Display the specified activity
     */
    public function show(Activity $activity)
    {
        $activity->load(['communityGroup', 'participants.member']);
        
        return Inertia::render('Activities/Show', [
            'activity' => $activity,
            'activityTypes' => Activity::ACTIVITY_TYPES,
            'statuses' => Activity::STATUSES,
        ]);
    }

    /**
     * Show the form for editing the specified activity
     */
    public function edit(Activity $activity)
    {
        $communityGroups = CommunityGroup::select('id', 'name')->orderBy('name')->get();
        
        return Inertia::render('Activities/Edit', [
            'activity' => $activity,
            'activityTypes' => Activity::ACTIVITY_TYPES,
            'statuses' => Activity::STATUSES,
            'communityGroups' => $communityGroups,
        ]);
    }

    /**
     * Update the specified activity
     */
    public function update(Request $request, Activity $activity)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'activity_type' => 'required|string|in:' . implode(',', array_keys(Activity::ACTIVITY_TYPES)),
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'start_time' => 'nullable|date_format:H:i',
                'end_time' => 'nullable|date_format:H:i|after:start_time',
                'location' => 'nullable|string|max:255',
                'organizer' => 'nullable|string|max:255',
                'community_group_id' => 'nullable|exists:community_groups,id',
                'max_participants' => 'nullable|integer|min:1',
                'registration_required' => 'boolean',
                'registration_deadline' => 'nullable|date|before_or_equal:start_date',
                'status' => 'required|string|in:' . implode(',', array_keys(Activity::STATUSES)),
                'notes' => 'nullable|string',
            ]);

            $activity->update($validated);

            return redirect()->route('activities.index')
                ->with('success', 'Activity updated successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to update activity: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Failed to update activity. Please try again.');
        }
    }

    /**
     * Remove the specified activity
     */
    public function destroy(Activity $activity)
    {
        try {
            $activity->delete();
            
            return redirect()->route('activities.index')
                ->with('success', 'Activity deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to delete activity: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete activity. Please try again.');
        }
    }

    /**
     * Get upcoming activities
     */
    public function recent(Request $request)
    {
        $activities = Activity::upcoming()
            ->with(['communityGroup'])
            ->limit(10)
            ->get();

        return response()->json($activities);
    }

    /**
     * Get activity statistics
     */
    public function getStatistics()
    {
        $stats = [
            'total_activities' => Activity::count(),
            'upcoming_activities' => Activity::upcoming()->count(),
            'active_activities' => Activity::where('status', 'active')->count(),
            'completed_activities' => Activity::where('status', 'completed')->count(),
            'this_month_activities' => Activity::thisMonth()->count(),
            'activities_by_type' => Activity::select('activity_type', DB::raw('count(*) as count'))
                ->groupBy('activity_type')
                ->get()
                ->pluck('count', 'activity_type'),
        ];

        return response()->json($stats);
    }

    /**
     * Search activities
     */
    public function search(Request $request)
    {
        $search = $request->get('q', '');
        
        $activities = Activity::where(function ($query) use ($search) {
            $query->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
        })
        ->with(['communityGroup'])
        ->limit(20)
        ->get();

        return response()->json($activities);
    }

    /**
     * Get activities for a specific member
     */
    public function memberActivities(Member $member)
    {
        $activities = $member->activities()
            ->with(['communityGroup'])
            ->orderBy('start_date', 'desc')
            ->paginate(15);

        return Inertia::render('Members/Activities', [
            'member' => $member,
            'activities' => $activities,
        ]);
    }

    /**
     * Bulk delete activities
     */
    public function bulkDelete(Request $request)
    {
        try {
            $validated = $request->validate([
                'activity_ids' => 'required|array',
                'activity_ids.*' => 'exists:activities,id'
            ]);

            $deletedCount = Activity::whereIn('id', $validated['activity_ids'])->delete();

            return back()->with('success', "Successfully deleted {$deletedCount} activities.");

        } catch (\Exception $e) {
            Log::error('Failed to bulk delete activities: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete activities. Please try again.');
        }
    }

    /**
     * Export activities
     */
    public function export(Request $request)
    {
        // This would typically use a job or export class
        // For now, return a simple response
        return back()->with('info', 'Activity export functionality is under development.');
    }

    /**
     * Import activities
     */
    public function import(Request $request)
    {
        // This would typically use a job or import class
        // For now, return a simple response
        return back()->with('info', 'Activity import functionality is under development.');
    }
}
