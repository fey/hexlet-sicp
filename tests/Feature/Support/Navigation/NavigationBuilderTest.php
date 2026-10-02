<?php

namespace Tests\Feature\Support\Navigation;

use App\DTO\Navigation\NavigationData;
use App\DTO\Navigation\NavItemData;
use App\Models\User;
use App\Support\Navigation\NavigationBuilder;
use Illuminate\Http\Request;
use Tests\TestCase;

class NavigationBuilderTest extends TestCase
{
    public function testGuestGetsLoginAndRegister(): void
    {
        $nav = $this->build(null);

        $this->assertContains(route('login'), $this->hrefs($nav->user));
        $this->assertContains(route('register'), $this->hrefs($nav->user));
        $this->assertNotContains(route('logout'), $this->hrefs($nav->user));
        $this->assertNull($this->adminItem($nav));
    }

    public function testUserGetsAccountMenuWithoutAdmin(): void
    {
        $user = User::factory()->regular()->create();

        $nav = $this->build($user);

        $this->assertSame(
            [route('users.show', $user), route('settings.account.index'), route('my.show'), route('logout')],
            $this->hrefs($nav->user),
        );
        $logout = collect($nav->user)->firstWhere('href', route('logout'));
        $this->assertSame('post', $logout->method);
        $this->assertNull($this->adminItem($nav));
    }

    public function testAdminGetsAdminMenuWithExport(): void
    {
        $nav = $this->build(User::factory()->admin()->create());

        $admin = $this->adminItem($nav);
        $this->assertNotNull($admin);
        $this->assertSame(
            [
                route('admin.users.index'),
                route('admin.comments.index'),
                route('admin.solutions.index'),
                route('admin.export.index'),
            ],
            $this->hrefs($admin->children),
        );
    }

    public function testOnlyMigratedRoutesAreInertiaLinks(): void
    {
        $builder = $this->builder(null);
        $isInertia = fn(array $items) => collect($items)
            ->mapWithKeys(fn(NavItemData $item) => [$item->href => $item->inertia])
            ->all();

        $this->assertSame(
            [route('settings.profile.index') => true, route('settings.account.index') => true],
            $isInertia($builder->settings()),
        );
        $this->assertFalse($isInertia($builder->build()->main)[route('chapters.index')]);
    }

    private function build(?User $user): NavigationData
    {
        return $this->builder($user)->build();
    }

    private function builder(?User $user): NavigationBuilder
    {
        $request = Request::create('/');
        $request->setUserResolver(fn() => $user);

        return new NavigationBuilder($request);
    }

    /**
     * @param array<int, NavItemData> $items
     * @return array<int, string>
     */
    private function hrefs(array $items): array
    {
        return array_map(fn(NavItemData $item) => $item->href, $items);
    }

    private function adminItem(NavigationData $nav): ?NavItemData
    {
        return collect($nav->main)->first(fn(NavItemData $item) => $item->children !== []);
    }
}
