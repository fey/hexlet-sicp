<?php

namespace Tests\Feature\Http\Controllers;

use Database\Seeders\ChaptersTableSeeder;
use Database\Seeders\ExercisesTableSeeder;
use Qameta\Allure\Attribute\DisplayName;
use Qameta\Allure\Attribute\Feature;
use Tests\ControllerTestCase;

#[Feature('Мое обучение')]
class MyControllerTest extends ControllerTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->seed([
            ChaptersTableSeeder::class,
            ExercisesTableSeeder::class,
        ]);

        $this->actingAs($this->user);
    }

    #[DisplayName('Страница дашборда обучения доступна залогиненному пользователю')]
    public function testShow(): void
    {
        $response = $this->get(route('my'));
        $response->assertOk();
    }
}
