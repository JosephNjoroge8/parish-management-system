<?php

namespace App\Helpers;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatabaseCompatibilityHelper
{
    /**
     * Check if current database connection is SQLite
     */
    public static function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    /**
     * Check if current database connection is MySQL
     */
    public static function isMysql(): bool
    {
        return DB::connection()->getDriverName() === 'mysql';
    }
    
    /**
     * Get compatible query for month filtering
     * Works with both SQLite and MySQL
     */
    public static function monthYearQuery(Builder $query, string $column, int $month, int $year): Builder
    {
        if (self::isSqlite()) {
            return $query->whereRaw("strftime('%m', {$column}) = ?", [sprintf('%02d', $month)])
                         ->whereRaw("strftime('%Y', {$column}) = ?", [sprintf('%04d', $year)]);
        }
        
        // MySQL and other drivers use Laravel's built-in methods
        return $query->whereMonth($column, $month)
                     ->whereYear($column, $year);
    }
    
    /**
     * Get compatible query for filtering within current month
     */
    public static function currentMonthQuery(Builder $query, string $column): Builder
    {
        $now = Carbon::now();
        return self::monthYearQuery($query, $column, $now->month, $now->year);
    }
    
    /**
     * Get compatible query for filtering within current year
     */
    public static function currentYearQuery(Builder $query, string $column): Builder
    {
        if (self::isSqlite()) {
            return $query->whereRaw("strftime('%Y', {$column}) = ?", [sprintf('%04d', Carbon::now()->year)]);
        }
        
        // MySQL and other drivers use Laravel's built-in methods
        return $query->whereYear($column, Carbon::now()->year);
    }
    
    /**
     * Get date formatting for month/year statistics
     * Works with both SQLite and MySQL
     */
    public static function getMonthYearFormat(string $column): string
    {
        if (self::isSqlite()) {
            return "strftime('%Y-%m', {$column})";
        }
        
        // MySQL format
        return "DATE_FORMAT({$column}, '%Y-%m')";
    }
    
    /**
     * Get start of month comparison for counting new records
     */
    public static function getStartOfMonthComparison(string $column): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        
        // Return as parameterized query to avoid SQL injection
        return [
            'query' => "CASE WHEN {$column} >= ? THEN 1 ELSE 0 END",
            'bindings' => [$startOfMonth->toDateTimeString()]
        ];
    }
    
    /**
     * Get raw SQL date format compatible with current database driver
     */
    public static function dateFormat(string $column, string $format): string
    {
        if (self::isSqlite()) {
            // Convert PHP date format to SQLite strftime format
            $sqliteFormat = str_replace(
                ['Y', 'm', 'd', 'H', 'i', 's'],
                ['%Y', '%m', '%d', '%H', '%M', '%S'],
                $format
            );
            return "strftime('{$sqliteFormat}', {$column})";
        }
        
        // MySQL date format
        return "DATE_FORMAT({$column}, '{$format}')";
    }
    
    /**
     * Get compatible now() function
     */
    public static function now(): string
    {
        return self::isSqlite() ? "datetime('now')" : "NOW()";
    }
    
    /**
     * Add days to a date in SQL
     */
    public static function addDays(string $column, int $days): string
    {
        if (self::isSqlite()) {
            return "datetime({$column}, '+{$days} days')";
        }
        
        return "DATE_ADD({$column}, INTERVAL {$days} DAY)";
    }
    
    /**
     * Subtract days from a date in SQL
     */
    public static function subDays(string $column, int $days): string
    {
        if (self::isSqlite()) {
            return "datetime({$column}, '-{$days} days')";
        }
        
        return "DATE_SUB({$column}, INTERVAL {$days} DAY)";
    }
    
    /**
     * Cast a value to a specific database type
     */
    public static function cast(string $column, string $type): string
    {
        if (self::isSqlite()) {
            if ($type === 'date') {
                return "date({$column})";
            }
            
            // SQLite simple casting
            return "CAST({$column} AS {$type})";
        }
        
        // MySQL casting
        return "CAST({$column} AS {$type})";
    }
}