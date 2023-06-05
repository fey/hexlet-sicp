<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Comment;
use App\Models\Exercise;
use Database\Seeders\ChaptersTableSeeder;
use Database\Seeders\ExercisesTableSeeder;
use Qameta\Allure\Attribute\Description;
use Qameta\Allure\Attribute\DisplayName;
use Qameta\Allure\Attribute\Feature;
use Tests\ControllerTestCase;

#[Feature('Упражнения')]
class ExerciseControllerTest extends ControllerTestCase
{
    private Exercise $exercise;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            ChaptersTableSeeder::class,
            ExercisesTableSeeder::class,
        ]);

        $exercise = Exercise::first();
        $exercise->comments()->saveMany(
            Comment::factory()
                ->count(5)
                ->user($this->user)
                ->commentable($exercise)
                ->make()
        );

        $this->exercise = $exercise;

        $this->actingAs($this->user);
    }

    #[DisplayName('Список упражнений доступен')]
    #[Description('https://sicp.hexlet.io/exercises')]
    public function testIndex(): void
    {
        $response = $this->get(route('exercises.index'));
        $response->assertOk();
    }

    #[DisplayName('Страница упражнения доступна')]
    #[Description('https://sicp.hexlet.io/exercises/3')]
    public function testShow(): void
    {
        $response = $this->get(route('exercises.show', $this->exercise));
        $response->assertOk();
    }
}
