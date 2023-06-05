<?php

namespace Tests\Feature\Http\Controllers;

use Qameta\Allure\Attribute\Description;
use Qameta\Allure\Attribute\DisplayName;
use Qameta\Allure\Attribute\Feature;
use Tests\ControllerTestCase;

#[Feature('Публичный профиль')]
class UserControllerTest extends ControllerTestCase
{
    #[DisplayName('Страница профиля пользователя доступна')]
    #[Description('https://sicp.hexlet.io/users/4')]
    public function testShow(): void
    {
        $response = $this->get(route('users.show', $this->user));

        $response->assertOk();
    }
}
