<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Ricerca case-insensitive su colonne JSON (Spatie translatable) in Filament Tables.
 *
 * Su MySQL/MariaDB il LIKE sul tipo JSON può essere case-sensitive; usiamo LOWER + CAST.
 */
final class CaseInsensitiveJsonColumnSearch
{
    public static function whereMatches(Builder $query, string $columnName, string $search): Builder
    {
        [$sql, $bindings] = self::jsonColumnLowerLikeSql($query, $columnName, $search);

        return $query->whereRaw($sql, $bindings);
    }

    public static function orWhereMatches(Builder $query, string $columnName, string $search): Builder
    {
        [$sql, $bindings] = self::jsonColumnLowerLikeSql($query, $columnName, $search);

        return $query->orWhereRaw($sql, $bindings);
    }

    /**
     * @return array{0: string, 1: array<int, string>}
     */
    private static function jsonColumnLowerLikeSql(Builder $query, string $columnName, string $search): array
    {
        $column = $query->qualifyColumn($columnName);
        $driver = $query->getConnection()->getDriverName();
        $needle = '%'.mb_strtolower($search, 'UTF-8').'%';

        $expr = match ($driver) {
            'pgsql' => 'LOWER(CAST('.$column.' AS TEXT))',
            'mysql', 'mariadb' => 'LOWER(CAST('.$column.' AS CHAR(16383) CHARACTER SET utf8mb4))',
            default => 'LOWER(CAST('.$column.' AS TEXT))',
        };

        return [$expr.' LIKE ?', [$needle]];
    }
}
