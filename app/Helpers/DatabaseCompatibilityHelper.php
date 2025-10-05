<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

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
    public static function monthYearQuery(Builder|EloquentBuilder $query, string $column, int $month, int $year): Builder|EloquentBuilder
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
    public static function currentMonthQuery(Builder|EloquentBuilder $query, string $column): Builder|EloquentBuilder
    {
        $now = Carbon::now();

        return self::monthYearQuery($query, $column, $now->month, $now->year);
    }

    /**
     * Get compatible query for filtering within current year
     */
    public static function currentYearQuery(Builder|EloquentBuilder $query, string $column): Builder|EloquentBuilder
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
            'bindings' => [$startOfMonth->toDateTimeString()],
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
        return self::isSqlite() ? "datetime('now')" : 'NOW()';
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

    /**
     * Get the appropriate year extraction SQL for the current database
     */
    public static function yearFunction(string $column): string
    {
        return self::isSqlite()
            ? "strftime('%Y', {$column})"
            : "YEAR({$column})";
    }

    /**
     * Get the appropriate month extraction SQL for the current database
     */
    public static function monthFunction(string $column): string
    {
        return self::isSqlite()
            ? "strftime('%m', {$column})"
            : "MONTH({$column})";
    }

    /**
     * Add a year filter to a query builder that works with both SQLite and MySQL
     */
    public static function whereYear(Builder|EloquentBuilder $query, string $column, int $year)
    {
        if (self::isSqlite()) {
            return $query->whereRaw("strftime('%Y', {$column}) = ?", [$year]);
        }

        return $query->whereYear($column, $year);
    }

    /**
     * Add a month filter to a query builder that works with both SQLite and MySQL
     */
    public static function whereMonth(Builder|EloquentBuilder $query, string $column, int $month)
    {
        if (self::isSqlite()) {
            return $query->whereRaw("strftime('%m', {$column}) = ?", [str_pad($month, 2, '0', STR_PAD_LEFT)]);
        }

        return $query->whereMonth($column, $month);
    }

    /**
     * Get distinct years from a table column
     */
    public static function getDistinctYears(Builder|EloquentBuilder $query, string $column): array
    {
        $yearExpression = self::isSqlite()
            ? "DISTINCT strftime('%Y', {$column}) as year"
            : "DISTINCT YEAR({$column}) as year";

        return $query->select(DB::raw($yearExpression))
            ->whereNotNull($column)
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Create a case statement that works with both databases for counting by year/month
     */
    public static function caseWhenYearMonth(string $column, int $year, int $month, string $thenValue = '1', string $elseValue = '0'): string
    {
        if (self::isSqlite()) {
            $monthPadded = str_pad($month, 2, '0', STR_PAD_LEFT);

            return "CASE WHEN strftime('%Y', {$column}) = '{$year}' AND strftime('%m', {$column}) = '{$monthPadded}' THEN {$thenValue} ELSE {$elseValue} END";
        }

        return "CASE WHEN YEAR({$column}) = {$year} AND MONTH({$column}) = {$month} THEN {$thenValue} ELSE {$elseValue} END";
    }

    /**
     * Create a case statement that works with both databases for counting by year only
     */
    public static function caseWhenYear(string $column, int $year, string $thenValue = '1', string $elseValue = '0'): string
    {
        if (self::isSqlite()) {
            return "CASE WHEN strftime('%Y', {$column}) = '{$year}' THEN {$thenValue} ELSE {$elseValue} END";
        }

        return "CASE WHEN YEAR({$column}) = {$year} THEN {$thenValue} ELSE {$elseValue} END";
    }

    /**
     * Create a case statement that works with both databases for counting by month only
     */
    public static function caseWhenMonth(string $column, int $month, string $thenValue = '1', string $elseValue = '0'): string
    {
        if (self::isSqlite()) {
            $monthPadded = str_pad($month, 2, '0', STR_PAD_LEFT);

            return "CASE WHEN strftime('%m', {$column}) = '{$monthPadded}' THEN {$thenValue} ELSE {$elseValue} END";
        }

        return "CASE WHEN MONTH({$column}) = {$month} THEN {$thenValue} ELSE {$elseValue} END";
    }
}
