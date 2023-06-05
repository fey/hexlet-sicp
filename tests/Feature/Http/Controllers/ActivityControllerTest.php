<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Chapter;
use App\Services\ActivityService;
use Database\Seeders\ChaptersTableSeeder;
use Qameta\Allure\Attribute\DisplayName;
use Qameta\Allure\Attribute\Feature;
use Tests\ControllerTestCase;

#[Feature('Лог активностей')]
class ActivityControllerTest extends ControllerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            ChaptersTableSeeder::class,
        ]);

        $this->actingAs($this->user);
    }

    #[DisplayName('В лог активностей записывается добавление глав')]
    public function testStoreAddChapters(): void
    {
        $chapters = Chapter::limit(3)->get();

        $response = $this->post(route('users.chapters.store', [$this->user]), [
                'chapters_id' => $chapters->pluck('id')->toArray(),
        ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('activity_log', [
            'description' => ActivityService::ACTIVITY_CHAPTER_ADDED,
            'causer_id' => $this->user->id,
        ]);
    }

    #[DisplayName('В лог активностей записывается удаление глав')]
    public function testStoreRemovedChapters(): void
    {
        $chapters = Chapter::limit(3)->get();
        $this->user->chapters()->saveMany($chapters);

        $response = $this->post(route('users.chapters.store', [$this->user->id]), [
            'chapters_id' => [],
        ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('activity_log', [
            'description' => ActivityService::ACTIVITY_CHAPTER_REMOVED,
            'causer_id' => $this->user->id,
        ]);
    }
}
