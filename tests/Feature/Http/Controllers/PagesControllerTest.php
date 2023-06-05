<?php

namespace Tests\Feature\Http\Controllers;

use Qameta\Allure\Attribute\DisplayName;
use Qameta\Allure\Attribute\Feature;
use Tests\TestCase;

#[Feature('Раздел страниц')]
class PagesControllerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * test show page about.
     *
     * @return void
     */
    #[DisplayName('Просмотр страницы о проекте')]
    public function testShowAbout(): void
    {
        $response = $this->get(route('pages.show', ['page' => 'about']));
        $response->assertOk();
    }
}
