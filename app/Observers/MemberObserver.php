<?php

namespace App\Observers;

use App\Models\Member;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MemberObserver
{
    /**
     * Handle the Member "created" event.
     */
    public function created(Member $member): void
    {
        $this->clearStatsCache();
        Log::info('New member created', ['member_id' => $member->id, 'name' => $member->full_name]);
    }

    /**
     * Handle the Member "updated" event.
     */
    public function updated(Member $member): void
    {
        // Clear cache when member status changes
        if ($member->wasChanged('membership_status')) {
            $this->clearStatsCache();
            Log::info('Member status updated', [
                'member_id' => $member->id,
                'name' => $member->full_name,
                'old_status' => $member->getOriginal('membership_status'),
                'new_status' => $member->membership_status
            ]);
        }
        
        // Clear cache if other important fields change
        if ($member->wasChanged(['local_church', 'church_group'])) {
            $this->clearStatsCache();
        }
    }

    /**
     * Handle the Member "deleted" event.
     */
    public function deleted(Member $member): void
    {
        $this->clearStatsCache();
        Log::info('Member deleted', ['member_id' => $member->id, 'name' => $member->full_name]);
    }

    /**
     * Handle the Member "restored" event.
     */
    public function restored(Member $member): void
    {
        $this->clearStatsCache();
        Log::info('Member restored', ['member_id' => $member->id, 'name' => $member->full_name]);
    }

    /**
     * Handle the Member "force deleted" event.
     */
    public function forceDeleted(Member $member): void
    {
        $this->clearStatsCache();
        Log::info('Member force deleted', ['member_id' => $member->id, 'name' => $member->full_name]);
    }

    /**
     * Clear statistics cache
     */
    private function clearStatsCache(): void
    {
        Cache::forget('parish_stats');
        Cache::forget('dashboard_stats');
        Cache::forget('member_counts');
        Cache::forget('church_stats');
    }
}
