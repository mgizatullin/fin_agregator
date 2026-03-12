<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait HandlesLoadMorePagination
{
    protected function loadMoreResponse(
        Request $request,
        LengthAwarePaginator $paginator,
        string $partialView,
        array $data = []
    ): ?JsonResponse {
        if (! $request->boolean('load_more')) {
            return null;
        }

        return response()->json([
            'html' => view($partialView, $data)->render(),
            'has_more' => $paginator->hasMorePages(),
            'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
        ]);
    }
}
