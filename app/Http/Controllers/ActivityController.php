<?php

namespace App\Http\Controllers;

use App\DTO\Activity\ActivityItemData;
use App\DTO\Activity\ActivityPageData;
use App\DTO\PaginationData;
use App\Models\Activity;
use App\Models\Solution;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Inertia\Response;

class ActivityController extends Controller
{
    public function index(): Response
    {
        $logItems = Activity::with([
            'causer',
            // Comment подгружает commentable сам, через $with.
            'subject' => fn(MorphTo $morphTo) => $morphTo->morphWith([Solution::class => ['exercise']]),
        ])
            ->orderBy('created_at', 'DESC')
            ->paginate(15)
            ->withQueryString();

        $page = new ActivityPageData(
            items: array_map(ActivityItemData::fromModel(...), $logItems->items()),
            pagination: PaginationData::fromPaginator($logItems),
        );

        // SSR в фазе 1 нет, ленте поисковая выдача не нужна (ADR 0004).
        return $this->inertia($page->toArray())
            ->withViewData(['robots' => 'noindex']);
    }
}
