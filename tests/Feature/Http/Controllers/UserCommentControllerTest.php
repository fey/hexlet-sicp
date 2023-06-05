<?php

namespace Tests\Feature\Http\Controllers;

use Qameta\Allure\Attribute\Description;
use Qameta\Allure\Attribute\DisplayName;
use Qameta\Allure\Attribute\Feature;
use Tests\ControllerTestCase;

#[Feature('Комментарии')]
class UserCommentControllerTest extends ControllerTestCase
{
    #[DisplayName('Страница с комментариями пользователя доступна')]
    #[Description('https://sicp.hexlet.io/users/4/comments')]
    public function testUserCommentIndex(): void
    {
        $response = $this->get(route('users.comments.index', $this->user));

        $response->assertOk();
    }
}
