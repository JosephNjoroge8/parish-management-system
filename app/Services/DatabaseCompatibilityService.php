<?php

namespace App\Services;

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

/**
 * Database Compatibility Service
 * 
 * This service provides methods that work across different database systems
 * ensuring compatibility between SQLite (development) and MySQL (production)
 */
class DatabaseCompatibilityService
{
    /**
     * Returns whether the current database connection is SQLite
     *
     * @return bool
     */
    public function isSQLite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    /**
     * Returns whether the current database connection is MySQL
     *
     * @return bool
     */
    public function isMySQL(): bool
    {
        return DB::connection()->getDriverName() === 'mysql';
    }
    
    /**
     * Get current date expression that works in both SQLite and MySQL
     *
     * @return \Illuminate\Database\Query\Expression
     */
    public function currentDate(): Expression
    {
        if ($this->isSQLite()) {
            return DB::raw("date('now')");
        }
        
        return DB::raw('CURDATE()');
    }

    /**
     * Get current timestamp expression that works in both SQLite and MySQL
     *
     * @return \Illuminate\Database\Query\Expression
     */
    public function currentTimestamp(): Expression
    {
        if ($this->isSQLite()) {
            return DB::raw("datetime('now')");
        }
        
        return DB::raw('NOW()');
    }

    /**
     * Extract month from date column in a way compatible with both SQLite and MySQL
     *
     * @param string $column The date column name
     * @return \Illuminate\Database\Query\Expression
     */
    public function extractMonth(string $column): Expression
    {
        if ($this->isSQLite()) {
            return DB::raw("CAST(strftime('%m', {$column}) AS INTEGER)");
        }
        
        return DB::raw("MONTH({$column})");
    }

    /**
     * Extract year from date column in a way compatible with both SQLite and MySQL
     *
     * @param string $column The date column name
     * @return \Illuminate\Database\Query\Expression
     */
    public function extractYear(string $column): Expression
    {
        if ($this->isSQLite()) {
            return DB::raw("CAST(strftime('%Y', {$column}) AS INTEGER)");
        }
        
        return DB::raw("YEAR({$column})");
    }

    /**
     * Format date in a way compatible with both SQLite and MySQL
     *
     * @param string $column The date column name
     * @param string $format The format (MySQL format)
     * @return \Illuminate\Database\Query\Expression
     */
    public function formatDate(string $column, string $format): Expression
    {
        if ($this->isSQLite()) {
            // Convert MySQL format to SQLite format (simplistic, covers basic cases)
            $sqliteFormat = str_replace(
                ['%Y', '%y', '%m', '%d', '%H', '%h', '%i', '%s'],
                ['%Y', '%Y', '%m', '%d', '%H', '%H', '%M', '%S'],
                $format
            );
            return DB::raw("strftime('{$sqliteFormat}', {$column})");
        }
        
        return DB::raw("DATE_FORMAT({$column}, '{$format}')");
    }

    /**
     * Safe SUBSTRING that works in both databases
     * 
     * @param string $column The column or string to extract from
     * @param int $start The starting position (1-indexed for MySQL compatibility)
     * @param int|null $length The length to extract (null for all remaining)
     * @return \Illuminate\Database\Query\Expression
     */
    public function substring(string $column, int $start, ?int $length = null): Expression
    {
        if ($this->isSQLite()) {
            if ($length !== null) {
                return DB::raw("substr({$column}, {$start}, {$length})");
            }
            return DB::raw("substr({$column}, {$start})");
        }
        
        if ($length !== null) {
            return DB::raw("SUBSTRING({$column}, {$start}, {$length})");
        }
        return DB::raw("SUBSTRING({$column}, {$start})");
    }
    
    /**
     * Cast to integer in a database-agnostic way
     *
     * @param string $expression The expression to cast
     * @return \Illuminate\Database\Query\Expression
     */
    public function castAsInteger(string $expression): Expression
    {
        if ($this->isSQLite()) {
            return DB::raw("CAST({$expression} AS INTEGER)");
        }
        
        return DB::raw("CAST({$expression} AS UNSIGNED)");
    }

    /**
     * Create a safe ORDER BY statement for numeric extraction from strings
     *
     * @param string $column The column containing numeric values within text
     * @param string $prefix The text prefix before the numeric part
     * @param string $direction The sort direction ('asc' or 'desc')
     * @return \Illuminate\Database\Query\Expression
     */
    public function orderByNumericPart(string $column, string $prefix, string $direction = 'asc'): Expression
    {
        $prefixLength = strlen($prefix);
        $direction = strtoupper($direction);
        
        if ($this->isSQLite()) {
            return DB::raw("CAST(SUBSTR({$column}, {$prefixLength} + 1) AS INTEGER) {$direction}");
        }
        
        return DB::raw("CAST(SUBSTRING({$column}, {$prefixLength} + 1) AS UNSIGNED) {$direction}");
    }
}