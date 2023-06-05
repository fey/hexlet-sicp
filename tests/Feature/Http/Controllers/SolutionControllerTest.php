<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Exercise;
use App\Models\Solution;
use Database\Seeders\ChaptersTableSeeder;
use Database\Seeders\ExercisesTableSeeder;
use Qameta\Allure\Attribute\DisplayName;
use Qameta\Allure\Attribute\Feature;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\ControllerTestCase;

#[Feature('Сохраненные решения')]
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

    #[DisplayName('Страница сохраненных решений доступна')]
    public function testIndex(): void
    {
        $route = route('solutions.index');

        $response = $this->get($route);

        $response->assertOk();
    }

    #[DisplayName('На странице сохраненных решений работает фильтр')]
    public function testIndexWithFilter(): void
    {
        $route = route('solutions.index');

        $response = $this->get($route, ['exercise_id' => $this->exercise->id]);

        $response->assertOk();
    }

    #[DisplayName('Страница сохраненного решения доступна')]
    public function testShow(): void
    {
        $route = route('solutions.show', $this->solution);

        $response = $this->get($route);
        $response->assertOk();
    }

    #[DisplayName('Страница сохраненного решения удаленного пользователя недоступна')]
    public function testShowSolutionOfTrashedUser(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->solution->user->delete();

        $route = route('solutions.show', $this->solution);

        $response = $this->get($route);

        $response->assertNotFound();
    }
}
