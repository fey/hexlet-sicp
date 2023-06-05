<?php

namespace Tests\Feature\Http\Controllers;

use Qameta\Allure\Attribute\Description;
use Qameta\Allure\Attribute\DisplayName;
use Qameta\Allure\Attribute\Feature;
use Tests\TestCase;

#[DisplayName('Открытие главной страницы')]
#[Feature("Главная страница")]
class HomeControllerTest extends TestCase
{
    #[Description("Возможность открытия главной страницы гостем")]
    public function testIndex(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
    }

    #[Description("Ссылка для dev-логина видна скрыта")]
    public function testNotSeeDevLogin(): void
    {
        $response = $this->get(route('home'));

        $response->assertDontSee(
            route('auth.dev-login')
        );
    }
}
