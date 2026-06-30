<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Http\Request;

trait HandlesApiIndexPagination
{
    private function shouldPaginate(Request $request): bool
    {
        if ($request->boolean('all')) {
            return false;
        }

        if ($request->has('paginate') && $request->boolean('paginate') === false) {
            return false;
        }

        return true;
    }

    private function perPage(Request $request, int $default = 20, int $max = 200): int
    {
        $perPage = (int) $request->query('per_page', $default);

        if ($perPage < 1) {
            $perPage = $default;
        }

        if ($perPage > $max) {
            $perPage = $max;
        }

        return $perPage;
    }
}
