<?php

namespace Tests\Feature\Http\Controllers\Settings;

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\ControllerTestCase;

class AccountControllerTest extends ControllerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->user);
    }

    public function testIndex(): void
    {
        $this->get(route('settings.account.index'))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->component('Settings/Account/Index')
                ->where('email', $this->user->email)
                ->where('resetPasswordUrl', route('password.request'))
                ->where('destroyUrl', route('settings.account.destroy', $this->user))
                ->has('menu', 2, fn(Assert $item) => $item->where('inertia', true)->etc())
                ->etc());
    }

    public function testDestroy(): void
    {
        $response = $this->delete(route('settings.account.destroy', $this->user));
        $response->assertRedirect(route('home'));
        $this->assertGuest();

        $this->assertNull(User::find($this->user->id));
    }

    public function testDestroyFromInertiaPageReloadsToHome(): void
    {
        $this->delete(route('settings.account.destroy', $this->user), [], ['X-Inertia' => 'true'])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', route('home'));

        $this->assertGuest();
        $this->assertNull(User::find($this->user->id));
    }

    public function testDestroyShowsFlashOnHome(): void
    {
        $this->followingRedirects()
            ->delete(route('settings.account.destroy', $this->user))
            ->assertSee(__('account.your_account_deleted'));
    }
}
