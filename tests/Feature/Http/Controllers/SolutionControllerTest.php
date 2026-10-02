<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Exercise;
use App\Models\Solution;
use App\Models\User;
use Database\Seeders\ChaptersTableSeeder;
use Database\Seeders\ExercisesTableSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\ControllerTestCase;

class SolutionControllerTest extends ControllerTestCase
{
    private Exercise $exercise;
    private Solution $solution;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed([
            ChaptersTableSeeder::class,
            ExercisesTableSeeder::class,
        ]);
        $solutions = Solution::factory()->count(5)->create();
        $this->user->solutions()->saveMany($solutions);

        $this->exercise = Exercise::first();
        $this->solution = Solution::first();

        $this->actingAs($this->user);
    }

    public function testIndex(): void
    {
        $latest = Solution::factory()->for($this->user)->create(['created_at' => now()->addMinute()]);

        $this->get(route('solutions.index'))
            ->assertOk()
            // Публичная страница индексируется, SSR нет — принятый SEO-долг (ADR 0004).
            ->assertDontSee('noindex', false)
            ->assertInertia(fn(Assert $page) => $page
                ->component('Solution/Index')
                ->where('pagination.total', 6)
                ->where('filterUrl', route('solutions.index'))
                ->where('filter', ['userName' => null, 'exerciseId' => null])
                ->has('exercises', Exercise::count())
                ->where('exercises.0', [
                    'value' => (string) Exercise::orderBy('id')->first()->id,
                    'label' => Exercise::orderBy('id')->first()->present()->fullTitle,
                ])
                ->has('tabs', 2)
                ->where('tabs.0.href', route('exercises.index'))
                ->where('tabs.0.inertia', false)
                ->where('tabs.1.href', route('solutions.index'))
                ->where('tabs.1.active', true)
                ->where('tabs.1.inertia', true)
                ->has('items', 6)
                ->has('items.0', fn(Assert $item) => $item
                    ->where('id', $latest->id)
                    ->where('userName', $this->user->name)
                    ->where('userUrl', route('users.show', $this->user))
                    ->where('userAvatarUrl', $this->user->present()->getProfileImageLink())
                    ->where('exerciseTitle', $latest->exercise->getFullTitle())
                    ->where('exerciseUrl', route('exercises.show', $latest->exercise))
                    ->where('createdAt', $latest->created_at->format('d.m.Y H:i'))
                    ->where('showUrl', route('solutions.show', $latest))));
    }

    public function testIndexFiltersByUserName(): void
    {
        $author = User::factory()->create(['name' => 'Zebediah Quux']);
        $solution = Solution::factory()->for($author)->create();

        $this->get(route('solutions.index', ['filter' => ['user.name' => 'ebediah q']]))
            ->assertInertia(fn(Assert $page) => $page
                ->has('items', 1)
                ->where('items.0.id', $solution->id)
                ->where('filter.userName', 'ebediah q')
                ->etc());
    }

    public function testIndexFiltersByExercise(): void
    {
        $exercise = Exercise::orderByDesc('id')->first();
        $solution = Solution::factory()->for($exercise)->create();

        $this->get(route('solutions.index', ['filter' => ['exercise_id' => $exercise->id]]))
            ->assertInertia(fn(Assert $page) => $page
                ->has('items', 1)
                ->where('items.0.id', $solution->id)
                ->where('filter.exerciseId', (string) $exercise->id)
                ->etc());
    }

    public function testIndexIgnoresEmptyAndArrayFilter(): void
    {
        // Очищенный Select отправляет пустую строку — bigint = '' ронял бы PostgreSQL.
        $this->get(route('solutions.index', ['filter' => ['user.name' => '', 'exercise_id' => '']]))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page->where('pagination.total', 5)->etc());

        $this->get(route('solutions.index', ['filter' => ['user.name' => ['x']]]))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page->where('filter.userName', null)->etc());
    }

    public function testPaginationKeepsFilter(): void
    {
        $exercise = Exercise::orderByDesc('id')->first();
        // versioned() оставляет одну версию на пару автор–упражнение, поэтому авторы разные.
        Solution::factory()->count(51)->for($exercise)->state(fn() => ['user_id' => User::factory()])->create();
        $filter = ['filter' => ['exercise_id' => (string) $exercise->id]];

        $this->get(route('solutions.index', $filter))
            ->assertInertia(fn(Assert $page) => $page
                ->where('pagination.total', 51)
                ->where('pagination.lastPage', 2)
                ->where('pagination.links.2.url', route('solutions.index', [...$filter, 'page' => 2]))
                ->etc());
    }

    public function testShow(): void
    {
        $route = route('solutions.show', $this->solution);

        $response = $this->get($route);
        $response->assertOk();
    }

    public function testShowSolutionOfTrashedUser(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $this->solution->user->delete();

        $route = route('solutions.show', $this->solution);

        $response = $this->get($route);

        $response->assertNotFound();
    }
}
