<?php

namespace Tests\Feature\Http\Controllers;

use Qameta\Allure\Attribute\Description;
use Qameta\Allure\Attribute\DisplayName;
use Qameta\Allure\Attribute\Feature;
use Tests\TestCase;


#[Feature("Главная страница")]
class HomeControllerTest extends TestCase
{
    #[DisplayName('Открытие главной страницы')]
    #[Description("Возможность открытия главной страницы гостем")]
    public function testIndex(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee(
            route('auth.dev-login')
        );
    }
}
