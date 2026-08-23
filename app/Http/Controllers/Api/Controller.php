<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Collection;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Wrap a query in the standard `{data, links, meta}` envelope by
     * paginating and returning the LengthAwarePaginator directly.
     * Laravel's JsonResponse renders paginators with that envelope.
     */
    protected function paginated(Builder $query, int $perPage = 25): LengthAwarePaginator
    {
        return $query->paginate(min($perPage, 100));
    }

    /** @param Collection<int,Model> $items */
    protected function paginatedCollection(Collection $items, int $perPage = 25): LengthAwarePaginator
    {
        $page = request()->integer('page', 1);

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page'],
        );
    }
}
