<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DatabaseHelper
{
    /**
     * Get age calculation SQL that works with both MySQL and SQLite
     */
    public static function getAgeSQL(string $dateColumn, ?string $referenceDate = null): string
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");
        
        $refDate = $referenceDate ?: 'date("now")';
        
        switch ($connection) {
            case 'mysql':
                return "TIMESTAMPDIFF(YEAR, {$dateColumn}, {$refDate})";
            
            case 'sqlite':
                // SQLite age calculation using julianday
                return "CAST((julianday({$refDate}) - julianday({$dateColumn})) / 365.25 AS INTEGER)";
            
            case 'pgsql':
                return "EXTRACT(YEAR FROM AGE({$refDate}, {$dateColumn}))";
                
            default:
                // Fallback for other databases
                return "CAST((julianday({$refDate}) - julianday({$dateColumn})) / 365.25 AS INTEGER)";
        }
    }
    
    /**
     * Use the newer DatabaseCompatibilityHelper instead of this class for better
     * cross-database compatibility
     * 
     * @deprecated Use DatabaseCompatibilityHelper instead
     */
    public static function getConnection(): string
    {
        return DB::connection()->getDriverName();
    }
    
    /**
     * Get members by age group using database-agnostic SQL
     */
    public static function getMembersByAgeGroup(string $ageGroup): int
    {
        $ageSQL = self::getAgeSQL('date_of_birth');
        
        return match ($ageGroup) {
            'children' => DB::table('members')
                ->whereRaw("({$ageSQL}) BETWEEN 0 AND 12")
                ->count(),
            'youth' => DB::table('members')
                ->whereRaw("({$ageSQL}) BETWEEN 13 AND 24")
                ->count(),
            'adults' => DB::table('members')
                ->whereRaw("({$ageSQL}) BETWEEN 25 AND 59")
                ->count(),
            'seniors' => DB::table('members')
                ->whereRaw("({$ageSQL}) >= 60")
                ->count(),
            default => 0,
        };
    }
    
    /**
     * Apply age filter to query builder
     */
    public static function applyAgeFilter($query, int $minAge = null, int $maxAge = null)
    {
        $ageSQL = self::getAgeSQL('date_of_birth');
        
        if ($minAge !== null && $maxAge !== null) {
            $query->whereRaw("({$ageSQL}) BETWEEN ? AND ?", [$minAge, $maxAge]);
        } elseif ($minAge !== null) {
            $query->whereRaw("({$ageSQL}) >= ?", [$minAge]);
        } elseif ($maxAge !== null) {
            $query->whereRaw("({$ageSQL}) <= ?", [$maxAge]);
        }
        
        return $query;
    }
}