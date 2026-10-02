<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Exercise;
use App\Models\ExerciseMember;
use Database\Seeders\ChaptersTableSeeder;
use Database\Seeders\ExercisesTableSeeder;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\ControllerTestCase;

class UserControllerTest extends ControllerTestCase
{
    public function testShow(): void
    {
        $response = $this->get(route('users.show', $this->user));

        $response->assertOk();
    }

    #[TestWith(['1 Building Abstractions with Procedures'], 'chapter with children')]
    #[TestWith(['1.1.1 Expressions'], 'leaf chapter')]
    public function testShowRendersChapterNameWithoutTrailingDot(string $chapterName): void
    {
        $this->seed(ChaptersTableSeeder::class);

        $response = $this->get(route('users.show', $this->user));

        $response->assertSee($chapterName);
        $response->assertDontSee("{$chapterName}.");
    }

    public function testShowRendersChapterProgressTree(): void
    {
        $this->seed([
            ChaptersTableSeeder::class,
            ExercisesTableSeeder::class,
        ]);
        $exercise = Exercise::wherePath('1.1')->firstOrFail();
        ExerciseMember::factory()->user($this->user)->exercise($exercise)->create();

        $response = $this->get(route('users.show', $this->user));

        $response->assertViewHas('chaptersProgress', fn($progress) => $progress->count() === 5);
        $response->assertSee(route('exercises.show', $exercise));
    }
}
