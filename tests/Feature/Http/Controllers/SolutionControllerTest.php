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
        $route = route('solutions.index');

        $response = $this->get($route);

        $response->assertOk();
    }

    public function testIndexWithFilter(): void
    {
        $route = route('solutions.index');

        $response = $this->get($route, ['exercise_id' => $this->exercise->id]);

        $response->assertOk();
    }

    public function testShow(): void
    {
        $exercise = $this->solution->exercise;
        $versions = $exercise->solutions()->where('user_id', $this->user->id)->orderBy('id')->get();

        $this->get(route('solutions.show', $this->solution))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertInertia(fn(Assert $page) => $page
                ->component('Solution/Show')
                ->where('title', __('solution.solution_for_title', ['exercise' => $exercise->getFullTitle()]))
                ->where('exerciseTitle', $exercise->getFullTitle())
                ->where('exerciseUrl', route('exercises.show', $exercise))
                ->where('userName', $this->user->name)
                ->where('userUrl', route('users.show', $this->user))
                ->has('versions', $versions->count())
                ->where('versions.0.id', $versions->first()->id)
                ->where('versions.0.content', $versions->first()->content));
    }

    public function testShowComparesEveryVersionOfExercise(): void
    {
        $exercise = $this->solution->exercise;
        $this->user->solutions()->saveMany(Solution::factory()->count(2)->for($exercise)->make());
        // Чужое решение того же упражнения в сравнение не попадает.
        Solution::factory()->for($exercise)->for(User::factory())->create();

        $ids = $exercise->solutions()->where('user_id', $this->user->id)->orderBy('id')->pluck('id')->all();

        $this->get(route('solutions.show', $this->solution))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->component('Solution/Show')
                ->where('versions', fn($versions) => collect($versions)->pluck('id')->all() === $ids)
                ->etc());
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
