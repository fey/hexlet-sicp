<?php

namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;
use Qameta\Allure\Attribute\Title;

#[Title("Проверка главной страницы")]
class HomeControllerTest extends TestCase
{
    #[Title("Возможность открытия главной страницы гостем")]
    public function testIndex(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
    }

    #[Title("Ссылка для dev-логина не видна")]
    public function testNotSeeDevLogin(): void
    {
        $response = $this->get(route('home'));

        $response->assertDontSee(
            route('auth.dev-login')
        );
    }
}
