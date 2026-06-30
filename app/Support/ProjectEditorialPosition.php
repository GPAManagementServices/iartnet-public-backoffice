<?php

namespace App\Support;

use App\Models\Project;

/**
 * Normalizes editorial positions and enforces exclusive positive slots per context.
 */
final class ProjectEditorialPosition
{
    public static function normalizeOrder(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '' || $trimmed === '0') {
                return null;
            }

            if (! is_numeric($trimmed)) {
                return null;
            }

            $value = (int) $trimmed;
        }

        if (is_float($value)) {
            $value = (int) $value;
        }

        if (! is_int($value) || $value <= 0) {
            return null;
        }

        return $value;
    }

    public static function applyNormalization(Project $project): void
    {
        $project->homepage_order = self::normalizeOrder($project->homepage_order);
        $project->projects_order = self::normalizeOrder($project->projects_order);
    }

    /**
     * When the saved project claims a positive position, clear that slot on other rows (same context only).
     * Uses query builder so dissociated rows keep their original updated_at.
     */
    public static function dissociateConflictingPositions(Project $project): void
    {
        if ($project->id === null) {
            return;
        }

        $homepageOrder = self::normalizeOrder($project->homepage_order);
        if ($homepageOrder !== null) {
            Project::query()
                ->whereKeyNot($project->id)
                ->where('homepage_order', $homepageOrder)
                ->update(['homepage_order' => null]);
        }

        $projectsOrder = self::normalizeOrder($project->projects_order);
        if ($projectsOrder !== null) {
            Project::query()
                ->whereKeyNot($project->id)
                ->where('projects_order', $projectsOrder)
                ->update(['projects_order' => null]);
        }
    }
}
