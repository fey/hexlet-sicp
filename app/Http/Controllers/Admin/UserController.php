<?php

namespace App\Http\Controllers\Admin;

use App\DTO\Admin\UpdateUserData;
use App\DTO\Admin\UserFilterData;
use App\DTO\Admin\UserListItemData;
use App\DTO\Admin\UserListPageData;
use App\DTO\PaginationData;
use App\Models\User;
use App\Support\Navigation\NavigationBuilder;
use Illuminate\Http\Request;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends AdminController
{
    public function index(Request $request, NavigationBuilder $navigation): Response
    {
        $users = QueryBuilder::for(User::class)
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::partial('email'),
            )
            ->latest()
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $page = new UserListPageData(
            items: array_map(UserListItemData::fromModel(...), $users->items()),
            pagination: PaginationData::fromPaginator($users),
            filter: UserFilterData::fromQuery($request),
            filterUrl: route('admin.users.index'),
            menu: $navigation->admin($request->only('filter')),
        );

        return $this->inertia($page->toArray())
            ->withViewData(['robots' => 'noindex, nofollow']);
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(UpdateUserData $request, User $user)
    {
        $user->update([
            'name' => $request->name,
            'github_name' => $request->github_name,
            'is_admin' => $request->is_admin,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated');
    }
}
